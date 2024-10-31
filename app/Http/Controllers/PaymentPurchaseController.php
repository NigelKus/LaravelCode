<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Journal;
use App\Models\Posting;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\PaymentPurchase;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Utils\AccountingEvents\AE_PO4_FinishPurchasePaymentKas;
use App\Utils\AccountingEvents\AE_PO3_FinishPurchasePaymentBank;

class PaymentPurchaseController extends Controller

{

    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }

        $statuses = ['pending', 'completed']; 

        $query = PaymentPurchase::with(['supplier' => function ($q) {
            $q->withTrashed(); 
        }])
        ->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

    
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
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
        $paymentPurchases = $query->paginate($perPage);

        return view('layouts.transactional.payment_purchase.index', compact('paymentPurchases', 'statuses')); // Adjust the view as necessary
    }

    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


        $suppliers = Supplier::where('status', 'active')->get();
    
        return view('layouts.transactional.payment_purchase.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


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
            'supplier_id' => 'required|exists:mstr_supplier,id', 
            'description' => 'nullable|string|max:255', 
            'date' => 'required|date', 
            'requested.*' => 'required|numeric|min:0',
            'invoice_id' => 'required|array', 
            'invoice_id.*' => 'exists:purchase_invoice,id', 
            'payment_type' => 'required',
        ]);
        
        $purchasePaymentCode = CodeFactory::generatePaymentPurchaseCode();
        DB::beginTransaction();
        $paymentPurchase = PaymentPurchase::create([
            'code' => $purchasePaymentCode,
            'supplier_id' => $request['supplier_id'],
            'description' => $request['description'],
            'date' => $request['date'],
            'status' => 'pending',
        ]);
        
        foreach ($request['invoice_id'] as $index => $invoiceId) {
            $requestedAmount = $request['requested'][$index];
            
            $paymentPurchase->paymentDetails()->create([
                'payment_id' => $paymentPurchase->id,
                'invoicepurchase_id' => $invoiceId,
                'price' => $requestedAmount, 
                'status' => 'pending',
            ]);
            $invoice = PurchaseInvoice::with('details')->findOrFail($invoiceId);

            if ($invoice->calculatePriceRemaining() == 0) 
            {
                $invoice->status = 'completed';
            }else 
            {
                $invoice->status = 'pending';   
            }

            $invoice->save();
        }

        $requiredAccounts = [
            1000 => "Chart of Account Code 1000 does not exist.",
            1100 => "Chart of Account Code 1100 does not exist.",
            2000 => "Chart of Account Code 2000 does not exist.",
        ];

        foreach ($requiredAccounts as $code => $errorMessage) {
            if (!ChartOfAccount::where("code", $code)->exists()) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $errorMessage]);
            }
        }
        
        if($request['payment_type'] == 'bank')
        {
            AE_PO3_FinishPurchasePaymentBank::process($paymentPurchase);
        }else 
        {
            
            AE_PO4_FinishPurchasePaymentKas::process($paymentPurchase);
        }
        DB::commit();
        return redirect()->route('payment_purchase.show', $paymentPurchase->id)->with('success', 'Payment Purchase created successfully.');
    }

    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


        $paymentPurchase = PaymentPurchase::with(['supplier' => function ($query) {
            $query->withTrashed();
        }, 'paymentDetails.purchaseInvoice'])
        ->findOrFail($id);

        $deleted = ($paymentPurchase->supplier->status == 'deleted');

        $totalPrice = $paymentPurchase->paymentDetails->sum(function ($detail) {
            return $detail->price;
        });

        $refType = 'App\Models\PaymentPurchase';

        $journal = Journal::where('ref_id', $paymentPurchase->id)
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

        return view('layouts.transactional.payment_purchase.show', [
            'paymentPurchase' => $paymentPurchase,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,
            'coas' => $coas,
            'deleted' => $deleted,
        ]);
    }

    public function edit(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


        $paymentPurchase = PaymentPurchase::with('paymentDetails')->findOrFail($id);
        $suppliers = $paymentPurchase->supplier;

        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();
        $posting = Posting::where('journal_id', $journal->id)->orderBy('id', 'desc')->first();
        $account = ChartOfAccount::where('code', 1000)
        ->where('status', 'active')
        ->first();

        if($posting->account_id == $account->id)
        {
            $paymentType = 'kas'; 
        }else $paymentType = 'bank';

        $purchaseInvoices = PurchaseInvoice::where('supplier_id', $suppliers->id)
            ->whereIn('status', ['pending', 'completed'])
            ->get();
    
        $combinedDetails = $purchaseInvoices->map(function ($invoice) use ($paymentPurchase) {
        $paymentDetail = $paymentPurchase->paymentDetails->firstWhere('invoicepurchase_id', $invoice->id);
        $remainingPrice = $invoice->calculatePriceRemaining() + ($paymentDetail->price ?? 0);

        if ($remainingPrice > 0) {
            return [
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->code,
                'requested' => $paymentDetail ? $paymentDetail->price : '',
                'original_price' => $invoice->getTotalPriceAttribute(),
                'remaining_price' => $remainingPrice,
            ];
        }
        })->filter();
        
        return view('layouts.transactional.payment_purchase.edit', [
            'paymentPurchase' => $paymentPurchase,
            'suppliers' => $suppliers, 
            'combinedDetails' => $combinedDetails,
            'payment_purchase_id' => $paymentPurchase->id, 
            'payment_purchase_code' => $paymentPurchase->code, 
            'payment_type' => $paymentType
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


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
            'payment_purchase_id' => 'required|exists:purchase_payment,id',
            'supplier_id' => 'required|exists:mstr_supplier,id', 
            'description' => 'nullable|string|max:255', 
            'date' => 'required|date', 
            'requested.*' => 'required|numeric|min:0', 
            'invoice_id' => 'required|array',
            'invoice_id.*' => 'exists:purchase_invoice,id', 
            'payment_type' => 'required',
        ]);

        $account1 = ChartOfAccount::where("code", 1000)->first();
        $account2 = ChartOfAccount::where("code", 1100)->first();
        $account3 = ChartOfAccount::where("code", 2000)->first();
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
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 2000 does not exist.']);
        }elseif($account1 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1000 and 2000 does not exist.']);
        }elseif($account3=2 == null && $account3 == null)
        {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Chart of Account Code 1100 and 2000 does not exist.']);
        }

        $paymentPurchase = PaymentPurchase::findOrFail($request->payment_purchase_id); // Find payment order or fail

        $paymentPurchase->update([
            'description' => $request->description,
            'date' => $request->date,
        ]);
        
        $orderDetails = $paymentPurchase->paymentDetails;

        $refType = 'App\Models\PaymentPurchase';

        $journal = Journal::where('ref_id', $paymentPurchase->id)
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
                } else {
                    $posting->amount = -abs($totalNewAmount);
                    $posting->account_id = $paymentType;
                }
                
                $posting->date = $journal->date;
                $posting->save(); 
                $firstRun = false; 
            }
        }

        foreach($orderDetails as $detail)
        {
            $invoice = PurchaseInvoice::with('details')->findOrFail($detail->invoicepurchase_id);
            if ($invoice->status === 'completed') {
                $invoice->status = 'pending';
                $invoice->save();
            }

            $detail->delete();
        }

        foreach ($request->invoice_id as $index => $invoiceId) {
            $paymentPurchase->paymentDetails()->updateOrCreate(
                ['invoicepurchase_id' => $invoiceId],
                [
                    'price' => $request->requested[$index],
                    'status' => 'pending',
                    'payment_id' => $paymentPurchase->id, 
                ]
            );

            $invoice = PurchaseInvoice::with('details')->findOrFail($invoiceId);
            if ($invoice->calculatePriceRemaining() == 0) 
            {
                $invoice->status = 'completed';
            }else 
            {
                $invoice->status = 'pending';   
            }

            $invoice->save();
        }
        
        return redirect()->route('payment_purchase.show', $paymentPurchase->id)->with('success', 'Payment Purchase updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


        $paymentPurchase = PaymentPurchase::findOrFail($id);
        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();
        foreach ($paymentPurchase->paymentDetails as $detail) {
            $invoice = PurchaseInvoice::with('details')->findOrFail($detail->invoicepurchase_id); 
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
        $paymentPurchase->update([
            'status' => 'deleted',
        ]);
    
        $paymentPurchase->delete();
    
        return redirect()->route('payment_purchase.index')->with('success', 'Payment Purchase and its details deleted successfully.');
    }
    

    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 3', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }


        $request->validate([
            'status' => 'required|in:pending,completed',
        ]);
        
        $paymentPurchase = PaymentPurchase::findOrFail($id);

        $paymentPurchase->update([
            'status' => $request->input('status'),
        ]);

        $paymentPurchase->save();
    
        return redirect()->route('payment_purchase.show', $paymentPurchase->id)->with('success', 'Sales invoice updated successfully.');
    }
}