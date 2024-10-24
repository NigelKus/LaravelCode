<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Models\PaymentOrder; 
use App\Utils\AccountingEvents\AE_S04_FinishSalesPaymentKas;
use App\Utils\AccountingEvents\AE_S03_FinishSalesPaymentBank;

class PaymentOrderController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; 

        $query = PaymentOrder::with(['customer' => function ($q) {
            $q->withTrashed(); 
        }])
        ->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
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
        $paymentOrders = $query->paginate($perPage);

        return view('layouts.transactional.payment_order.index', compact('paymentOrders', 'statuses')); // Adjust the view as necessary
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
    
        return view('layouts.transactional.payment_order.create', compact('customers'));
    }
    

    public function store(Request $request)
    {
        $inputData = $request->all();
        
        $filteredInvoiceIds = [];
        $filteredRequested = [];
        $filteredOriginalPrices = [];
        $filteredRemainingPrices = [];
        
        foreach ($inputData['invoice_id'] as $index => $invoiceId) {
            if (!empty($invoiceId) && !empty($inputData['requested'][$index])) {
                $filteredInvoiceIds[] = $invoiceId;
                $filteredRequested[] = $inputData['requested'][$index];
                $filteredOriginalPrices[] = $inputData['original_prices'][$index];
                $filteredRemainingPrices[] = $inputData['remaining_prices'][$index];
            }
        }
        
        $request->merge([
            'invoice_id' => $filteredInvoiceIds,
            'requested' => $filteredRequested,
            'original_prices' => $filteredOriginalPrices,
            'remaining_prices' => $filteredRemainingPrices,
        ]);
        
        $request->validate([
            'customer_id' => 'required|exists:mstr_customer,id',
            'description' => 'nullable|string|max:255', 
            'date' => 'required|date', 
            'requested.*' => 'required|numeric|min:0', 
            'invoice_id' => 'required|array', 
            'invoice_id.*' => 'exists:sales_invoice,id',
            'payment_type' => 'required',
        ]);

    
        $salesPaymentCode = CodeFactory::generatePaymentSalesCode();
        DB::beginTransaction();
        $paymentOrder = PaymentOrder::create([
            'code' => $salesPaymentCode,
            'customer_id' => $request['customer_id'],
            'description' => $request['description'],
            'date' => $request['date'],
            'status' => 'pending',
        ]);
        
        foreach ($request['invoice_id'] as $index => $invoiceId) {
            $requestedAmount = $request['requested'][$index];
            
            $paymentOrder->paymentDetails()->create([
                'payment_id' => $paymentOrder->id,
                'invoicesales_id' => $invoiceId,
                'price' => $requestedAmount, 
                'status' => 'pending',
            ]);
            $invoice = SalesInvoice::with('details')->findOrFail($invoiceId);

            if ($invoice->calculatePriceRemaining() == 0) 
            {
                $invoice->status = 'completed';
            }else 
            {
                $invoice->status = 'pending';   
            }

            $invoice->save();
        }
        $account1 = ChartOfAccount::where("code", 1000)->first();
        $account2 = ChartOfAccount::where("code", 1100)->first();
        $account3 = ChartOfAccount::where("code", 1200)->first();
        if($account1 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1000 does not exist.']);
        }elseif($account2 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1100 does not exist.']);
        }elseif($account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1200 does not exist.']);
        }elseif($account1 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1000 and 1200 does not exist.']);
        }elseif($account3=2 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1100 and 1200 does not exist.']);
        }

        if($request['payment_type'] == 'bank')
        {
            AE_S03_FinishSalesPaymentBank::process($paymentOrder);
        }else 
        {
            AE_S04_FinishSalesPaymentKas::process($paymentOrder);
        }

        DB::commit();
        return redirect()->route('payment_order.show', $paymentOrder->id)->with('success', 'Payment Order created successfully.');

    }
    
    public function show($id)
    {
        $paymentOrder = PaymentOrder::with(['customer' => function ($query) {
            $query->withTrashed();
        }, 'paymentDetails.salesInvoice'])
        ->findOrFail($id);

        $deleted = ($paymentOrder->customer->status == 'deleted');
    
        $totalPrice = $paymentOrder->paymentDetails->sum(function ($detail) {
            return $detail->price;
        });

        $refType = 'App\Models\PaymentOrder';

        $journal = Journal::where('ref_id', $paymentOrder->id)
                        ->where('ref_type', $refType)
                        ->first();
        $coas = [];
        $postings = collect();
        
        if ($journal) {
            $postings = Posting::where('journal_id', $journal->id)->get();
            foreach ($postings as $posting) {
                $coas[] = $posting->account()->withTrashed()->first(); 
            }
        }

        // dd($coas);
        return view('layouts.transactional.payment_order.show', [
            'paymentOrder' => $paymentOrder,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,  
            'coas' => $coas,
            'deleted' => $deleted,
        ]);
        
    }

    public function edit($id)
    {
        $paymentOrder = PaymentOrder::with('paymentDetails')->findOrFail($id);
        $customers = $paymentOrder->customer;

        $journal = Journal::where('ref_id', $paymentOrder->id)->first();
        $posting = Posting::where('journal_id', $journal->id)->first();

        $account = ChartOfAccount::where('code', 1000)
        ->where('status', 'active')
        ->first();

        if($posting->account_id == $account->id)
        {
            $paymentType = 'kas'; 
        }else $paymentType = 'bank';
        

        $salesInvoices = SalesInvoice::where('customer_id', $customers->id)
            ->whereIn('status', ['pending', 'completed'])
            ->get();
    
        $combinedDetails = $salesInvoices->map(function ($invoice) use ($paymentOrder) {
        $paymentDetail = $paymentOrder->paymentDetails->firstWhere('invoicesales_id', $invoice->id);
        $remainingPrice = $invoice->calculatePriceRemaining() + ($paymentDetail->price ?? 0);
    
            if ($remainingPrice > 0) {
                return [
                    'invoice_id' => $invoice->id,
                    'invoice_code' => $invoice->code,
                    'requested' => $paymentDetail ? $paymentDetail->price : '', 
                    'original_price' => $invoice->getTotalPriceAttribute(),
                    'remaining_price' => $remainingPrice
                ];
            }
        })->filter();

        return view('layouts.transactional.payment_order.edit', [
            'paymentOrder' => $paymentOrder,
            'customers' => $customers, 
            'combinedDetails' => $combinedDetails,
            'payment_order_id' => $paymentOrder->id,
            'payment_order_code' => $paymentOrder->code, 
            'payment_type' => $paymentType
        ]);
    }
    
    public function update(Request $request, $id)
    {

        $inputData = $request->all();
        
        $filteredInvoiceIds = [];
        $filteredRequested = [];
        $filteredOriginalPrices = [];
        $filteredRemainingPrices = [];
        
        foreach ($inputData['invoice_id'] as $index => $invoiceId) {
            if (!empty($invoiceId) && !empty($inputData['requested'][$index])) {
                $filteredInvoiceIds[] = $invoiceId;
                $filteredRequested[] = $inputData['requested'][$index];
                $filteredOriginalPrices[] = $inputData['original_prices'][$index];
                $filteredRemainingPrices[] = $inputData['remaining_prices'][$index];
            }
        }
        
        $request->merge([
            'invoice_id' => $filteredInvoiceIds,
            'requested' => $filteredRequested,
            'original_prices' => $filteredOriginalPrices,
            'remaining_prices' => $filteredRemainingPrices,
        ]);
        
        $request->validate([
            'payment_order_id' => 'required|exists:sales_payment,id',
            'customer_id' => 'required|exists:mstr_customer,id', 
            'description' => 'nullable|string|max:255', 
            'date' => 'required|date',
            'requested.*' => 'required|numeric|min:0', 
            'invoice_id' => 'required|array', 
            'invoice_id.*' => 'exists:sales_invoice,id', 
            'payment_type' => 'required',
        ]);

        $account1 = ChartOfAccount::where("code", 1000)->first();
        $account2 = ChartOfAccount::where("code", 1100)->first();
        $account3 = ChartOfAccount::where("code", 1200)->first();
        if($account1 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1000 does not exist.']);
        }elseif($account2 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1100 does not exist.']);
        }elseif($account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1200 does not exist.']);
        }elseif($account1 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1000 and 1200 does not exist.']);
        }elseif($account3=2 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1100 and 1200 does not exist.']);
        }

        $paymentOrder = PaymentOrder::findOrFail($request->payment_order_id);

        $paymentOrder->update([
            'description' => $request->description,
            'date' => $request->date,
        ]);
        
        $orderDetails = $paymentOrder->paymentDetails;

        $refType = 'App\Models\PaymentOrder';

        $journal = Journal::where('ref_id', $paymentOrder->id)
                        ->where('ref_type', $refType)
                        ->first();

        if ($journal) {
            $journal->date = Carbon::parse($request['date']);
            $journal->save();

            $postings = Posting::where('journal_id', $journal->id)->get();
            $totalNewAmount = 0;

            foreach ($request->requested as $requestedAmount) {
                if (!empty($requestedAmount)) {
                    $totalNewAmount += $requestedAmount; 
                }
            }
            
            if($request['payment_type'] == 'bank'){
                $paymentType = $account2->id;
            }else{
                $paymentType = $account1->id;
            }


            $firstRun = true; 
            foreach ($postings as $posting) {
                if ($firstRun) {
                        $posting->amount = abs($totalNewAmount);
                        $posting->account_id = $paymentType;
                } else {
                    $posting->amount = -abs($totalNewAmount);
                }

                $posting->date = $journal->date;
                $posting->save(); 
                $firstRun = false; 
            }
        }
        

        foreach($orderDetails as $detail)
        {
            $invoice = SalesInvoice::with('details')->findOrFail($detail->invoicesales_id);

            if ($invoice->status === 'completed') {
                $invoice->status = 'pending';
                $invoice->save();
            }

            $detail->delete();
        }

        foreach ($request->invoice_id as $index => $invoiceId) {
            $paymentOrder->paymentDetails()->updateOrCreate(
                ['invoicesales_id' => $invoiceId],
                [
                    'price' => $request->requested[$index],
                    'status' => 'pending',
                    'payment_id' => $paymentOrder->id, 
                ]
            );

            $invoice = SalesInvoice::with('details')->findOrFail($invoiceId);

            ($invoice->calculatePriceRemaining() == 0)  ? $invoice->status = 'completed' : $invoice->status = 'pending';   
            $invoice->save();
        }
        
        
        return redirect()->route('payment_order.show', $paymentOrder->id)->with('success', 'Payment Order updated successfully.');
    }

    public function destroy($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id);
        $journal = Journal::where('ref_id', $paymentOrder->id)->first();
    
        foreach ($paymentOrder->paymentDetails as $detail) {
            $invoice = SalesInvoice::with('details')->findOrFail($detail->invoicesales_id); 
            $invoice->status = 'pending';
            $invoice->save(); 

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
        
            $detail->update(['status' => 'deleted']);
            $detail->delete();
        }
        
    
        $paymentOrder->update([
            'status' => 'deleted',
        ]);
    
        $paymentOrder->delete();
    
        return redirect()->route('payment_order.index')->with('success', 'Payment Order and its details deleted successfully.');
    }
    

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed',
        ]);
        
        $paymentOrder = PaymentOrder::findOrFail($id);

        $paymentOrder->update([
            'status' => $request->input('status'),
        ]);

        $paymentOrder->save();
    
        return redirect()->route('payment_order.show', $paymentOrder->id)->with('success', 'Sales invoice updated successfully.');
    }

}
