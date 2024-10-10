<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PaymentPurchase;
use App\Models\PurchaseInvoice;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Utils\AccountingEvents\AE_PO3_FinishPurchasePaymentBank;
use App\Utils\AccountingEvents\AE_PO4_FinishPurchasePaymentKas;

class PaymentPurchaseController extends Controller

{

    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; // Define your statuses
        $query = PaymentPurchase::query();

        // Exclude invoices with status 'deleted' and 'canceled'
        $query->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

    
        // Apply status filter if present
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        // Apply code search filter if present
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        // Apply supplier search filter if present
        if ($request->has('supplier') && $request->supplier != '') {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%');
            });
        }

        // Apply date filter if present
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('date', $request->date);
        }
    
        // Apply sorting based on recent or oldest
        if ($request->has('sort')) {
            if ($request->sort == 'recent') {
                $query->orderBy('date', 'desc'); // Sort by date descending
            } elseif ($request->sort == 'oldest') {
                $query->orderBy('date', 'asc'); // Sort by date ascending
            }
        }
    
        // Determine items per page
        $perPage = $request->get('perPage', 10); // Default to 10 if not specified
        $paymentPurchases = $query->paginate($perPage);

        return view('layouts.transactional.payment_purchase.index', compact('paymentPurchases', 'statuses')); // Adjust the view as necessary
    }

    public function create()
    {
        // Fetch all active customers
        $suppliers = Supplier::where('status', 'active')->get();
    
        // Return the view with only the customers
        return view('layouts.transactional.payment_purchase.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        // Get the raw data from the request
        $inputData = $request->all();
        
        // Filter out any invoice lines where either the invoice_id or requested amount is null
        $filteredInvoiceIds = [];
        $filteredRequested = [];
        $filteredOriginalPrices = [];
        $filteredRemainingPrices = [];
        
        foreach ($inputData['invoice_id'] as $index => $invoiceId) {
            if (!empty($invoiceId) && !empty($inputData['requested'][$index])) {
                // Keep valid data
                $filteredInvoiceIds[] = $invoiceId;
                $filteredRequested[] = $inputData['requested'][$index];
                $filteredOriginalPrices[] = $inputData['original_prices'][$index];
                $filteredRemainingPrices[] = $inputData['remaining_prices'][$index];
            }
        }
        
        // Update the input data with the filtered values
        $request->merge([
            'invoice_id' => $filteredInvoiceIds,
            'requested' => $filteredRequested,
            'original_prices' => $filteredOriginalPrices,
            'remaining_prices' => $filteredRemainingPrices,
        ]);
        
        // Validate the request data (after filtering)
        $request->validate([
            'supplier_id' => 'required|exists:mstr_supplier,id', // Validate customer ID
            'description' => 'nullable|string|max:255', // Optional description
            'date' => 'required|date', // Validate date
            'requested.*' => 'required|numeric|min:0', // Validate requested amounts
            'invoice_id' => 'required|array', // Validate that invoice IDs are an array
            'invoice_id.*' => 'exists:purchase_invoice,id', // Validate each invoice ID exists
            'payment_type' => 'required',
        ]);
        
        // Generate the payment order code
        $purchasePaymentCode = CodeFactory::generatePaymentPurchaseCode();
        DB::beginTransaction();
        // Create the payment order
        $paymentPurchase = PaymentPurchase::create([
            'code' => $purchasePaymentCode,
            'supplier_id' => $request['supplier_id'],
            'description' => $request['description'],
            'date' => $request['date'],
            'status' => 'pending',
        ]);
        
        // Loop through the filtered data and create invoice lines  
        foreach ($request['invoice_id'] as $index => $invoiceId) {
            // Find the invoice

            $requestedAmount = $request['requested'][$index];
            
            // Create the payment detail for the invoice
            $paymentPurchase->paymentDetails()->create([
                'payment_id' => $paymentPurchase->id,
                'invoicepurchase_id' => $invoiceId,
                'price' => $requestedAmount, // Use the requested price or total price
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

    public function show($id)
    {
        $paymentPurchase = PaymentPurchase::with(['supplier', 'paymentDetails.purchaseInvoice', 'journal'])->findOrFail($id);

        // Calculate the total price from payment details
        $totalPrice = $paymentPurchase->paymentDetails->sum(function ($detail) {
            return $detail->price;
        });

        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();
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
        ]);
    }

    public function edit($id)
    {
        // Fetch the payment order and its details
        $paymentPurchase = PaymentPurchase::with('paymentDetails')->findOrFail($id);
        $suppliers = $paymentPurchase->supplier;

        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();
        $postings = Posting::where('journal_id', $journal->id)->get();

        $paymentType = 'bank'; // Default to bank
        foreach ($postings as $posting) {
            // Set paymentType based on account_id
            if ($posting->account_id == 1) {
                $paymentType = 'kas'; // Set to kas if account_id is 1
                break; // Exit the loop as we've determined the payment type
            }
        }
        // Fetch related sales invoices with status 'pending' or 'completed'
        $purchaseInvoices = PurchaseInvoice::where('supplier_id', $suppliers->id)
            ->whereIn('status', ['pending', 'completed'])
            ->get();
    
        // Prepare combined details and filter out invoices with remaining price 0
        $combinedDetails = $purchaseInvoices->map(function ($invoice) use ($paymentPurchase) {
        // Find the corresponding payment detail for this invoice
        $paymentDetail = $paymentPurchase->paymentDetails->firstWhere('invoicepurchase_id', $invoice->id);

        // Calculate remaining price
        $remainingPrice = $invoice->calculatePriceRemaining() + ($paymentDetail->price ?? 0);

        // Return only if remaining price is greater than 0
        if ($remainingPrice > 0) {
            return [
                'invoice_id' => $invoice->id, // Ensure this is assigned
                'invoice_code' => $invoice->code,
                'requested' => $paymentDetail ? $paymentDetail->price : '', // Existing payment detail, if any
                'original_price' => $invoice->getTotalPriceAttribute(),
                'remaining_price' => $remainingPrice,
            ];
        }
        })->filter(); // Use filter to remove null entries where remaining_price == 0
        

        // Continue with passing data to the view if needed
        return view('layouts.transactional.payment_purchase.edit', [
            'paymentPurchase' => $paymentPurchase,
            'suppliers' => $suppliers, // Use singular for clarity
            'combinedDetails' => $combinedDetails,
            'payment_purchase_id' => $paymentPurchase->id, // Send payment order ID
            'payment_purchase_code' => $paymentPurchase->code, // Send payment order code
            'payment_type' => $paymentType
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());

        $inputData = $request->all();
        
        // Filter out any invoice lines where either the invoice_id or requested amount is null
        $filteredInvoiceIds = [];
        $filteredRequested = [];
        $filteredOriginalPrices = [];
        $filteredRemainingPrices = [];
        
        foreach ($inputData['invoice_id'] as $index => $invoiceId) {
            if (!empty($invoiceId) && !empty($inputData['requested'][$index])) {
                // Keep valid data
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
            'payment_purchase_id' => 'required|exists:payment_purchase,id',
            'supplier_id' => 'required|exists:mstr_supplier,id', // Validate customer ID
            'description' => 'nullable|string|max:255', // Optional description
            'date' => 'required|date', // Validate date
            'requested.*' => 'required|numeric|min:0', // Validate requested amounts
            'invoice_id' => 'required|array', // Validate that invoice IDs are an array
            'invoice_id.*' => 'exists:purchase_invoice,id', // Validate each invoice ID exists
            'payment_type' => 'required',
        ]);
        $paymentPurchase = PaymentPurchase::findOrFail($request->payment_purchase_id); // Find payment order or fail

        $paymentPurchase->update([
            'description' => $request->description,
        ]);
        
        $orderDetails = $paymentPurchase->paymentDetails;

        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();

        
        if ($journal) {
            // Fetch postings related to this journal
            $postings = Posting::where('journal_id', $journal->id)->get();
            $totalNewAmount = 0;

            foreach ($request->requested as $requestedAmount) {
                if (!empty($requestedAmount)) {
                    $totalNewAmount += $requestedAmount; // Accumulate the requested amount
                }
            }
            
            if($request['payment_type'] == 'bank'){
                $paymentType = 8;
            }else{
                $paymentType = 1;
            }


            $firstRun = true; // Initialize a flag to track the first run
            foreach ($postings as $posting) {
                if ($firstRun) {
                        $posting->amount = abs($totalNewAmount);
                } else {
                    // Set to a negative amount on the second run
                    $posting->amount = -abs($totalNewAmount);
                    $posting->account_id = $paymentType;
                }
        
                $posting->save(); // Save each posting after updating
                $firstRun = false; // Toggle flag after the first iteration
            }
        }

        foreach($orderDetails as $detail)
        {
            $invoice = PurchaseInvoice::with('details')->findOrFail($detail->invoicepurchase_id);

            // If the status is 'completed', set it back to 'pending'
            if ($invoice->status === 'completed') {
                $invoice->status = 'pending';
                $invoice->save();
            }

            $detail->delete();
        }

        // Update or create payment order details
        foreach ($request->invoice_id as $index => $invoiceId) {
            // Create or update payment order details
            $paymentPurchase->paymentDetails()->updateOrCreate(
                ['invoicepurchase_id' => $invoiceId],
                [
                    'price' => $request->requested[$index],
                    'status' => 'pending',
                    'payment_id' => $paymentPurchase->id, // Insert the payment_order_id here
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

    public function destroy($id)
    {
        // Find the payment order or fail
        $paymentPurchase = PaymentPurchase::findOrFail($id);
        $journal = Journal::where('ref_id', $paymentPurchase->id)->first();
    
        // Loop through the payment order details and set their status to 'deleted'
        foreach ($paymentPurchase->paymentDetails as $detail) {
            // Retrieve the related sales invoice
            $invoice = PurchaseInvoice::with('details')->findOrFail($detail->invoicepurchase_id); // Assuming invoicesales_id is the related field
            $invoice->status = 'pending';
            $invoice->save(); // Save the updated status

            if ($journal) {
                // Fetch postings related to this journal
                $postings = Posting::where('journal_id', $journal->id)->get();
                foreach ($postings as $posting) {
                    $posting->update([
                        'status' => 'deleted'
                    ]);

                    $posting->save(); // Save each posting after updating
                    $posting->delete();
                }

                $journal->update([
                    'status' => 'deleted'
                ]);

                $journal->save(); // Save each posting after updating
                $journal->delete();
            }
        
            // Update payment detail status to 'deleted'
            $detail->update(['status' => 'deleted']);
            
            // Soft delete the payment order detail
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