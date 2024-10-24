<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use App\Models\PurchaseInvoice;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrderDetail;
use Database\Factories\CodeFactory;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseInvoiceDetail;
use App\Utils\AccountingEvents\AE_PO2_FinishPurchaseInvoice;

class PurchaseInvoiceController extends Controller

{
    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; 
        $purchaseOrders = PurchaseOrder::all(); 

        $query = PurchaseInvoice::with(['supplier' => function($q){
            $q->withTrashed();
        }])->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        $query->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        if ($request->has('purchase_order') && $request->purchase_order != '') {
            $query->where('code', 'like', '%' . $request->purchase_order . '%');
        }

        if ($request->has('supplier') && $request->supplier != '') {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%');
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
        $purchaseInvoices = $query->paginate($perPage);

        foreach ($purchaseInvoices as $invoice) {
            $totalPrice = $invoice->details->sum(function($detail) {
                return $detail->price * $detail->quantity; 
            });
            $invoice->total_price = $totalPrice;
        }
    
        return view('layouts.transactional.purchase_invoice.index', [
            'purchaseInvoices' => $purchaseInvoices,
            'statuses' => $statuses,
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'pending')->get();
        $purchaseOrdersDetail = PurchaseOrderDetail::where('status', 'pending')->get();
        
        return view('layouts.transactional.purchase_invoice.create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrdersDetail' => $purchaseOrdersDetail
        ]);
    }

    public function getInvoiceDetails($id)
    {
        $invoice = PurchaseInvoice::with('details')->findOrFail($id);
        $totalPrice = $invoice->details->sum(function($detail) {
            return $detail->price * $detail->quantity; 
        });
    
        $priceRemaining = $invoice->price_remaining; 
    
        return response()->json([
            'total_price' => $totalPrice,
            'price_remaining' => $priceRemaining,
        ]);
    }
    
                    
    public function getPurchaseInvoicesBySupplier($supplierId)
    {
        $purchaseInvoices = PurchaseInvoice::with('details')
            ->where('supplier_id', $supplierId)
            ->where('status', 'pending')
            ->get();
    
        $validInvoices = collect();

        $purchaseInvoices->each(function ($invoice) use ($validInvoices) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });
            $invoice->remaining_price = $invoice->calculatePriceRemaining();
            
            if ($invoice->remaining_price > 0) {
                $validInvoices->push($invoice);
            }
        });
        return response()->json(['purchaseInvoices' => $validInvoices]);
    }

    public function store(Request $request)
    {   
        
        $filteredData = collect($request->input('requested'))->filter(function ($value, $key) {
            return $value > 0; 
        })->keys()->toArray();

        $requestData = $request->all();
        foreach (['requested', 'qtys', 'price_eachs', 'price_totals', 'purchase_order_detail_ids'] as $field) {
            $requestData[$field] = array_intersect_key($requestData[$field], array_flip($filteredData));
        }

        $requestData['price_eachs'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_eachs'], array_flip($filteredData)));
        $requestData['price_totals'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_totals'], array_flip($filteredData)));

        $request->replace($requestData);
        
        $request->validate([
            'supplier_id' => 'required|exists:mstr_supplier,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'purchaseorder_id' => 'required|exists:purchase_order,id',
            'requested.*' => 'required|integer|min:1', 
            'qtys.*' => 'required|integer|min:1',
            'price_eachs.*' => 'required|numeric|min:0',
            'price_totals.*' => 'required|numeric|min:0',
            'purchase_order_detail_ids.*' => 'required|integer',
        ], [
            'requested.*.min' => 'Requested quantity must be at least 1.',
        ]);

        DB::beginTransaction();
        
            $purchaseInvoiceCode = CodeFactory::generatePurchaseInvoiceCode();

            $purchaseInvoice = new purchaseInvoice();
            $purchaseInvoice->code = $purchaseInvoiceCode; 
            $purchaseInvoice->purchaseorder_id = $request->input('purchaseorder_id');
            $purchaseInvoice->supplier_id = $request->input('supplier_id');
            $purchaseInvoice->description = $request->input('description');
            $purchaseInvoice->date = $request->input('date');
            $purchaseInvoice->due_date = $request->input('due_date');
            $purchaseInvoice->status = 'pending'; 
            $purchaseInvoice->save();
            
            $purchaseOrderId = $request->input('purchaseorder_id');
            
            $existingpurchaseOrder = PurchaseOrder::with('details')->find($purchaseOrderId); 
            if (!$existingpurchaseOrder) {
                throw new \Exception('purchase order not found.');
            }

            $requestedQuantities = $request->input('requested');
            $priceEaches = $request->input('price_eachs');
            $purchaseDetail = $request->input('purchase_order_detail_ids');
            foreach ($purchaseDetail as $index => $purchaseOrderDetailId) {
                $purchaseOrderDetail = $existingpurchaseOrder->details->where('id', $purchaseOrderDetailId)->first();

                if (!$purchaseOrderDetail) {
                    throw new \Exception('purchase order detail not found for ID ' . $purchaseOrderDetailId);
                }
                
                $productId = $purchaseOrderDetail->product_id;
                $requested = $requestedQuantities[$index] ?? 0;
                
                $purchaseInvoiceDetail = new PurchaseInvoiceDetail();
                $purchaseInvoiceDetail->purchaseinvoice_id = $purchaseInvoice->id;
                $purchaseInvoiceDetail->product_id = $productId;
                $purchaseInvoiceDetail->quantity = $requested;
                $purchaseInvoiceDetail->purchasedetail_id = $purchaseOrderDetail->id;
                $purchaseInvoiceDetail->price = $priceEaches[$index];
                $purchaseInvoiceDetail->status = 'pending'; 
                $purchaseInvoiceDetail->save();

                PurchaseOrderDetail::checkAndUpdateStatus($purchaseOrderId, $productId, $purchaseOrderDetailId);
                
            }
            
            $account1 = ChartOfAccount::where("code", 2000)->first();
            $account2 = ChartOfAccount::where("code", 4000)->first();
            if($account1 == null)
            {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Chart of Account Code 2000 does not exist.']);
            }elseif($account2 == null)
            {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Chart of Account Code 4000 does not exist.']);
            }elseif($account1 == null && $account2 == null )
            {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Chart of Account Code 2000 & 4000 does not exist.']);
            }
            
            AE_PO2_FinishPurchaseInvoice::process($purchaseInvoice);

            DB::commit();

            return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)
            ->with('success', 'purchase invoice updated successfully.');
    }

    public function show($id)
    {
        $purchaseInvoice = PurchaseInvoice::with(['supplier' => function ($query) {
            $query->withTrashed();
        }, 'details.product', 'purchaseOrder'])
        ->findOrFail($id);

        $deleted = ($purchaseInvoice->supplier->status == 'deleted');

        $totalPrice = $purchaseInvoice->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });

        $refType = 'App\Models\PurchaseInvoice';

        $journal = Journal::where('ref_id', $purchaseInvoice->id)
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
        
        return view('layouts.transactional.purchase_invoice.show', [
            'purchaseInvoice' => $purchaseInvoice,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,
            'coas' => $coas,
            'deleted' => $deleted,
        ]);
    }

    public function edit($id)
    {
        $purchaseInvoice = purchaseInvoice::with(['details.product'])->findOrFail($id);
        $purchaseInvoice->date = \Carbon\Carbon::parse($purchaseInvoice->date);
        $purchaseInvoice->due_date = \Carbon\Carbon::parse($purchaseInvoice->due_date);
        
        $suppliers = supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $purchaseOrders = purchaseOrder::where('status', 'pending')->get();

        $purchaseOrder = purchaseOrder::findOrFail($purchaseInvoice->purchaseorder_id);
    
        if ($purchaseOrder->status === 'canceled' || $purchaseOrder->status === 'deleted') {
            return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->withErrors([
                'error' => 'The purchase order has already been deleted or canceled.'
            ]);
        }
        
        $purchaseOrderDetails = purchaseOrderDetail::where('purchaseorder_id', $purchaseInvoice->purchaseorder_id)->get();
        
        $purchaseOrderDetailsMap = $purchaseOrderDetails->keyBy('product_id');
        
        return view('layouts.transactional.purchase_invoice.edit', [
            'purchaseInvoice' => $purchaseInvoice,
            'suppliers' => $suppliers,
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrderDetailsMap' => $purchaseOrderDetailsMap,
        ]);
    }
    
    public function update(Request $request, $id)
    {  
        $purchaseInvoice = purchaseInvoice::findOrFail($id);
        $purchaseInvoice->supplier_id = $request['supplier_id'];
        $purchaseInvoice->description = $request['description'];
        $purchaseInvoice->date = $request['date'];
        $purchaseInvoice->due_date = $request['due_date'];
        $purchaseInvoice->save();
        
        $purchaseInvoice = purchaseInvoice::with('details')->findOrFail($id);
        $invoiceDetails = $purchaseInvoice->details;

        $refType = 'App\Models\PurchaseInvoice';

        $journal = Journal::where('ref_id', $purchaseInvoice->id)
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

        $requested = $request['requested'];
        $priceEachs = $request['price_eachs'];
        $productIds = $request['product_id'];
        $purchase_order_id = $request['purchase_order_id'];

        foreach ($productIds as $i => $productId) {
            if ($productId !== null && (!empty($requested[$i]) && (int)$requested[$i] > 0)) { 
                $purchaseInvoiceDetail = new purchaseInvoiceDetail();
                $purchaseInvoiceDetail->purchaseinvoice_id = $purchaseInvoice->id;
                $purchaseInvoiceDetail->product_id = $productId; 
                $purchaseInvoiceDetail->quantity = $requested[$i] !== null ? (int)$requested[$i] : 0;
                $purchaseInvoiceDetail->purchasedetail_id = $request['purchasedetail_id'][$i] !== null ? (int)$request['purchasedetail_id'][$i] : null;
                $purchaseInvoiceDetail->price = $priceEachs[$i] !== null ? (float)str_replace(',', '', $priceEachs[$i]) : 0; 
                $purchaseInvoiceDetail->status = 'pending';
                
                
                $purchaseInvoiceDetail->save();
                purchaseorderDetail::checkAndUpdateStatus($purchase_order_id[$i], $productId, $request['purchasedetail_id'][$i]);
            }
        }
        return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)
            ->with('success', 'purchase invoice updated successfully.');
    }

    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled,deleted',
        ]);
        
        $purchaseInvoice = purchaseInvoice::findOrFail($id);

        $purchaseOrder = purchaseOrder::findOrFail($purchaseInvoice->purchaseorder_id);

        if ($purchaseOrder->status === 'canceled' || $purchaseOrder->status === 'deleted') {
            return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->withErrors([
                'error' => 'The purchase order has already been deleted or canceled. Status cannot be updated.'
            ]);
        }

        $purchaseInvoice->status = $request->input('status');
        $purchaseInvoice->save();

        foreach ($purchaseInvoice->details as $detail) {
            $detail->status = $request->input('status'); 
            $detail->save(); 
        }
    
        return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->with('success', 'purchase invoice updated successfully.');
    }
    

    public function destroy($id)
    {
        $purchaseInvoice = purchaseInvoice::findOrFail($id);

        $journal = Journal::where('ref_id', $purchaseInvoice->id)->first();

        $hasPaymentOrderDetail = false;

        foreach ($purchaseInvoice->details as $detail) {
            $paymentOrderDetail = \App\Models\PaymentPurchaseDetail::where('invoicepurchase_id', $purchaseInvoice->id)->exists();
        
            if ($paymentOrderDetail) {
                $hasPaymentOrderDetail = true;
                break; 
            }
        }
        
        if ($hasPaymentOrderDetail) {
            return redirect()->back()->withErrors(['error' => 'There is an ongoing payment related to this purchase invoice.']);
        }
        
        foreach ($purchaseInvoice->details as $detail) {
            try {
                $purchaseOrderDetail = $detail->purchaseOrderDetail; 

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
                
                $purchaseOrderDetail->adjustQuantityRemaining($detail->quantity); 
                $detail->update([
                    'status' => 'deleted',
                ]);
                
                $detail->delete(); 

                if ($purchaseOrderDetail->status === 'completed') {
                    $purchaseOrderDetail->update(['status' => 'pending']);
                    $purchaseOrder = $purchaseOrderDetail->purchaseOrder; 
                    if ($purchaseOrder && $purchaseOrder->status === 'completed') {
                        $purchaseOrder->update(['status' => 'pending']);
                    }
                }

            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }
        $purchaseInvoice->update([
            'status' => 'deleted',
        ]);
        
        $purchaseInvoice->delete();
    
        return redirect()->route('purchase_invoice.index')->with('success', 'purchase Invoice deleted successfully.');
    }
}