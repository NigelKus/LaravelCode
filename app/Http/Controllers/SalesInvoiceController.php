<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use Database\Factories\HPPFactory;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use App\Models\SalesorderDetail;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory; 
use App\Utils\AccountingEvents\AE_S02_FinishSalesInvoice;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $statuses = ['pending', 'completed']; 
        $salesOrders = SalesOrder::all(); 

        $query = SalesInvoice::with(['customer' => function($q){
            $q->withTrashed();
        }])->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        if ($request->has('sales_order') && $request->sales_order != '') {
            $query->where('code', 'like', '%' . $request->sales_order . '%');
        }

        if ($request->has('customer') && $request->customer != '') {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer . '%');
            });
        }
    
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('date', $request->date);
        }
    
        if ($request->has('sort')) {
            if ($request->sort == 'recent') {
                $query->orderBy('date', 'desc'); 
            } elseif ($request->sort == 'oldest') {
                $query->orderBy('date', 'asc'); 
            }
        }
    
        $perPage = $request->get('perPage', 10); 
        $salesInvoices = $query->paginate($perPage);

        foreach ($salesInvoices as $invoice) {
            $totalPrice = $invoice->details->sum(function($detail) {
                return $detail->price * $detail->quantity; 
            });
            $invoice->total_price = $totalPrice;
        }

    
        return view('layouts.transactional.sales_invoice.index', [
            'salesInvoices' => $salesInvoices,
            'statuses' => $statuses,
            'salesOrders' => $salesOrders
        ]);
    }

    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $salesOrders = SalesOrder::where('status', 'pending')->get();
    
        $salesOrdersDetail = SalesorderDetail::where('status', 'pending')->get();

        
        
        return view('layouts.transactional.sales_invoice.create', [
            'customers' => $customers,
            'products' => $products,
            'salesOrders' => $salesOrders,
            'salesOrdersDetail' => $salesOrdersDetail
        ]);
    }
    
    public function store(Request $request)
    {   
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }
        
        $filteredData = collect($request->input('requested'))->filter(function ($value, $key) {
            return $value > 0; 
        })->keys()->toArray();

        $requestData = $request->all();
        foreach (['requested', 'qtys', 'price_eachs', 'price_totals', 'sales_order_detail_ids'] as $field) {
            $requestData[$field] = array_intersect_key($requestData[$field], array_flip($filteredData));
        }

        $requestData['price_eachs'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_eachs'], array_flip($filteredData)));
        $requestData['price_totals'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_totals'], array_flip($filteredData)));

        $request->replace($requestData);
        
        $request->validate([
            'customer_id' => 'required|exists:mstr_customer,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'salesorder_id' => 'required|exists:sales_order,id',
            'requested.*' => 'required|integer|min:1', 
            'qtys.*' => 'required|integer|min:1',
            'price_eachs.*' => 'required|numeric|min:0',
            'price_totals.*' => 'required|numeric|min:0',
            'sales_order_detail_ids.*' => 'required|integer',
        ], [
            'requested.*.min' => 'Requested quantity must be at least 1.',
        ]);

        DB::beginTransaction();
        
            $salesInvoiceCode = CodeFactory::generateSalesInvoiceCode();

            $salesInvoice = new SalesInvoice();
            $salesInvoice->code = $salesInvoiceCode; 
            $salesInvoice->salesorder_id = $request->input('salesorder_id');
            $salesInvoice->customer_id = $request->input('customer_id');
            $salesInvoice->description = $request->input('description');
            $salesInvoice->date = $request->input('date');
            $salesInvoice->due_date = $request->input('due_date');
            $salesInvoice->status = 'pending'; 
            $salesInvoice->save();
            
            $salesOrderId = $request->input('salesorder_id');
            
            $existingSalesOrder = SalesOrder::with('details')->find($salesOrderId); 
            if (!$existingSalesOrder) {
                throw new \Exception('Sales order not found.');
            }

            $requestedQuantities = $request->input('requested');
            $priceEaches = $request->input('price_eachs');
            $salesDetail = $request->input('sales_order_detail_ids');

            $HPP = 0;
            foreach ($salesDetail as $index => $salesOrderDetailId) {
                $salesOrderDetail = $existingSalesOrder->details->where('id', $salesOrderDetailId)->first();

                if (!$salesOrderDetail) {
                    throw new \Exception('Sales order detail not found for ID ' . $salesOrderDetailId);
                }
                
                $productId = $salesOrderDetail->product_id;
                $requested = $requestedQuantities[$index] ?? 0;
                
                $salesInvoiceDetail = new SalesInvoiceDetail();
                $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
                $salesInvoiceDetail->product_id = $productId;
                $salesInvoiceDetail->quantity = $requested;
                $salesInvoiceDetail->salesdetail_id = $salesOrderDetail->id;
                $salesInvoiceDetail->price = $priceEaches[$index];
                $salesInvoiceDetail->status = 'pending'; 
                $salesInvoiceDetail->save();

                $HPP = $HPP + ($requested * HPPFactory::generateHPP($productId,  $request->input('date')));
                SalesorderDetail::checkAndUpdateStatus($salesOrderId, $productId, $salesOrderDetailId);
                
            }

            $salesInvoice->HPP = $HPP;

            $requiredAccounts = [
                1200 => "Chart of Account Code 1200 does not exist.",
                4000 => "Chart of Account Code 4000 does not exist.",
                4200 => "Chart of Account Code 4200 does not exist.",
                1300 => "Chart of Account Code 1300 does not exist.",
            ];

            foreach ($requiredAccounts as $code => $errorMessage) {
                if (!ChartOfAccount::where("code", $code)->exists()) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['error' => $errorMessage]);
                }
            }
            
            
            AE_S02_FinishSalesInvoice::process($salesInvoice);
            
            DB::commit();

            return redirect()->route('sales_invoice.show', $salesInvoice->id)
            ->with('success', 'Sales invoice updated successfully.');
    }


    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $salesInvoice = SalesInvoice::with(['customer' => function ($query) {
            $query->withTrashed();
        }, 'details.product', 'salesOrder'])
        ->findOrFail($id);

        $deleted = ($salesInvoice->customer->status == 'deleted');

        $totalPrice = $salesInvoice->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        
        $refType = 'App\Models\SalesInvoice';

        $journal = Journal::where('ref_id', $salesInvoice->id)
                        ->where('ref_type', $refType)
                        ->first();
        $coas = [];
        $postings = collect();

        if($journal){
            $postings = Posting ::where('journal_id', $journal->id)->get();
            foreach ($postings as $posting) {
                $coas[] = $posting->account()->withTrashed()->first(); 
            }
        }

        return view('layouts.transactional.sales_invoice.show', [
            'salesInvoice' => $salesInvoice,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,
            'coas' => $coas,
            'deleted' => $deleted,
        ]);
    }
    

    public function edit(Request $request, $id)
    {
        
        $salesInvoice = SalesInvoice::with(['details.product'])->findOrFail($id);
        
        $salesInvoice->date = \Carbon\Carbon::parse($salesInvoice->date);
        $salesInvoice->due_date = \Carbon\Carbon::parse($salesInvoice->due_date);
        
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $salesOrders = SalesOrder::where('status', 'pending')->get();

        $salesOrder = SalesOrder::findOrFail($salesInvoice->salesorder_id);
    
        if ($salesOrder->status === 'canceled' || $salesOrder->status === 'deleted') {
            return redirect()->route('sales_invoice.show', $salesInvoice->id)->withErrors([
                'error' => 'The sales order has already been deleted or canceled.'
            ]);
        }
        
        $salesOrderDetails = SalesOrderDetail::where('salesorder_id', $salesInvoice->salesorder_id)->get();
        
        $salesOrderDetailsMap = $salesOrderDetails->keyBy('product_id');
        
        return view('layouts.transactional.sales_invoice.edit', [
            'salesInvoice' => $salesInvoice,
            'customers' => $customers,
            'products' => $products,
            'salesOrders' => $salesOrders,
            'salesOrderDetailsMap' => $salesOrderDetailsMap,
        ]);
    }
    
    public function update(Request $request, $id)
    {   
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $salesInvoice = SalesInvoice::findOrFail($id);
    
        $salesInvoice->customer_id = $request['customer_id'];
        $salesInvoice->description = $request['description'];
        $salesInvoice->date = $request['date'];
        $salesInvoice->due_date = $request['due_date'];
        $salesInvoice->save();
        
        $salesInvoice = SalesInvoice::with('details')->findOrFail($id);
        $invoiceDetails = $salesInvoice->details;
        
        
        $refType = 'App\Models\SalesInvoice';

        $journal = Journal::where('ref_id', $salesInvoice->id)
                        ->where('ref_type', $refType)
                        ->first();
        
        $productIds = $request['product_id'];
        $requested = $request['requested'];
        $priceEachs = $request['price_eachs'];

        $priceEachsAcc = array_map(function($price) {
            return str_replace(',', '', $price); 
        }, $priceEachs);


        if ($journal) {
            $journal->date = Carbon::parse($request['date']);
            $journal->save();

            $postings = Posting::where('journal_id', $journal->id)->get();
            $totalNewAmount = 0;
        
            foreach ($requested as $i => $quantity) {
                if (!empty($priceEachsAcc[$i]) && $priceEachsAcc[$i] != 0 && $quantity != 0) {
                    $totalNewAmount += $priceEachsAcc[$i] * $quantity;
                }
            }
        
            $firstRun = true; 
            foreach ($postings as $posting) {
                if ($firstRun) {
                    $posting->amount = abs($totalNewAmount);
                } else {
                    $posting->amount = -abs($totalNewAmount);
                }
                
                $posting->date = $journal->date;
                $posting->save(); 
                $firstRun = false; 
            }
        }

        foreach ($invoiceDetails as $detail) {
            $detail->delete();
        }

        
        $sales_order_id = $request['sales_order_id'];


        foreach ($productIds as $i => $productId) {
            if ($productId !== null && (!empty($requested[$i]) && (int)$requested[$i] > 0)) { 
                $salesInvoiceDetail = new SalesInvoiceDetail();
                $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
                $salesInvoiceDetail->product_id = $productId; 
                $salesInvoiceDetail->quantity = $requested[$i] !== null ? (int)$requested[$i] : 0;
                $salesInvoiceDetail->salesdetail_id = $request['salesdetail_id'][$i] !== null ? (int)$request['salesdetail_id'][$i] : null;
                $salesInvoiceDetail->price = $priceEachs[$i] !== null ? (float)str_replace(',', '', $priceEachs[$i]) : 0; 
                $salesInvoiceDetail->status = 'pending';
                

                
                $salesInvoiceDetail->save();
                SalesorderDetail::checkAndUpdateStatus($sales_order_id[$i], $productId, $request['salesdetail_id'][$i]);
            }
        }
        return redirect()->route('sales_invoice.show', $salesInvoice->id)
            ->with('success', 'Sales invoice updated successfully.');
    }

    
    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'status' => 'required|in:pending,completed,cancelled,deleted',
        ]);
        
        $salesInvoice = SalesInvoice::findOrFail($id);

        $salesOrder = SalesOrder::findOrFail($salesInvoice->salesorder_id);

        if ($salesOrder->status === 'canceled' || $salesOrder->status === 'deleted') {
            return redirect()->route('sales_invoice.show', $salesInvoice->id)->withErrors([
                'error' => 'The sales order has already been deleted or canceled. Status cannot be updated.'
            ]);
        }

        $salesInvoice->status = $request->input('status');
        $salesInvoice->save();
        foreach ($salesInvoice->details as $detail) {
            $detail->status = $request->input('status'); 
            $detail->save(); 
        }
    
        return redirect()->route('sales_invoice.show', $salesInvoice->id)->with('success', 'Sales invoice updated successfully.');
    }
    

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 2'])) {
            abort(403, 'Unauthorized access');
        }

        $salesInvoice = SalesInvoice::findOrFail($id);
        $journal = Journal::where('ref_id', $salesInvoice->id)->first();

        $hasPaymentOrderDetail = false;
        foreach ($salesInvoice->details as $detail) {
            $paymentOrderDetail = \App\Models\PaymentOrderDetail::where('invoicesales_id', $salesInvoice->id)->exists();
        
            if ($paymentOrderDetail) {
                $hasPaymentOrderDetail = true;
                break;
            }
        }
        
        if ($hasPaymentOrderDetail) {
            return redirect()->back()->withErrors(['error' => 'There is an ongoing payment related to this sales invoice.']);
        }
        
        foreach ($salesInvoice->details as $detail) {
            try {
                $salesOrderDetail = $detail->salesOrderDetail; 

                if ($journal) {
                    $postings = Posting::where('journal_id', $journal->id)->get();
                    foreach ($postings as $posting) {
                        $posting->update([
                            'status' => 'deleted'
                        ]);

                        $posting->save(); 
                        $posting->delete();
                    }

                    $journal->update([
                        'status' => 'deleted'
                    ]);

                    $journal->save(); 
                    $journal->delete();
                }
                
                $salesOrderDetail->adjustQuantityRemaining($detail->quantity); 
                $detail->update([
                    'status' => 'deleted',
                ]);
                
                $detail->delete(); 
                if ($salesOrderDetail->status === 'completed') {
                    $salesOrderDetail->update(['status' => 'pending']);
    
                    $salesOrder = $salesOrderDetail->salesOrder; 
                    if ($salesOrder && $salesOrder->status === 'completed') {
                        $salesOrder->update(['status' => 'pending']);
                    }
                }

            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }
        $salesInvoice->update([
            'status' => 'deleted',
        ]);
        
        $salesInvoice->delete();
    
        return redirect()->route('sales_invoice.index')->with('success', 'Sales Invoice deleted successfully.');
    }
    
    public function getInvoiceDetails($id)
    {
        $invoice = SalesInvoice::with('details')->findOrFail($id);
        
        $totalPrice = $invoice->details->sum(function($detail) {
            return $detail->price * $detail->quantity; 
        });
    
        $priceRemaining = $invoice->price_remaining; 
    
        return response()->json([
            'total_price' => $totalPrice,
            'price_remaining' => $priceRemaining,
        ]);
    }
    
    
    public function getSalesInvoicesByCustomer($customerId)
    {
        $salesInvoices = SalesInvoice::with('details')
            ->where('customer_id', $customerId)
            ->where('status', 'pending')
            ->get();
    
        $validInvoices = collect();
    
        $salesInvoices->each(function ($invoice) use ($validInvoices) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });
            $invoice->remaining_price = $invoice->calculatePriceRemaining();

            
            if ($invoice->remaining_price > 0) {
                $validInvoices->push($invoice);
            }
        });
    
        return response()->json(['salesInvoices' => $validInvoices]);
    }
}