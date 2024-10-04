<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Models\PaymentOrder; // Ensure to import your model
use App\Utils\AccountingEvents\AE_S04_FinishSalesPaymentKas;
use App\Utils\AccountingEvents\AE_S03_FinishSalesPaymentBank;

class PaymentOrderController extends Controller
{
    // Display a listing of the payment orders
    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; // Define your statuses
        $query = PaymentOrder::query();

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
    
        // Apply customer search filter if present
        if ($request->has('customer') && $request->customer != '') {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer . '%');
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
        $paymentOrders = $query->paginate($perPage);

        return view('layouts.transactional.payment_order.index', compact('paymentOrders', 'statuses')); // Adjust the view as necessary
    }

    // Show the form for creating a new payment order
    public function create()
    {
        // Fetch all active customers
        $customers = Customer::where('status', 'active')->get();
    
        // Return the view with only the customers
        return view('layouts.transactional.payment_order.create', compact('customers'));
    }
    

    // Store a newly created payment order in storage
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
            'customer_id' => 'required|exists:mstr_customer,id', // Validate customer ID
            'description' => 'nullable|string|max:255', // Optional description
            'date' => 'required|date', // Validate date
            'requested.*' => 'required|numeric|min:0', // Validate requested amounts
            'invoice_id' => 'required|array', // Validate that invoice IDs are an array
            'invoice_id.*' => 'exists:invoice_sales,id', // Validate each invoice ID exists
            'payment_type' => 'required',
        ]);
        
        // Generate the payment order code
        $salesPaymentCode = CodeFactory::generatePaymentSalesCode();
        DB::beginTransaction();
        // Create the payment order
        $paymentOrder = PaymentOrder::create([
            'code' => $salesPaymentCode,
            'customer_id' => $request['customer_id'],
            'description' => $request['description'],
            'date' => $request['date'],
            'status' => 'pending',
        ]);
        
        // Loop through the filtered data and create invoice lines  
        foreach ($request['invoice_id'] as $index => $invoiceId) {
            // Find the invoice
            

            $requestedAmount = $request['requested'][$index];
            
            // Create the payment detail for the invoice
            $paymentOrder->paymentDetails()->create([
                'payment_id' => $paymentOrder->id,
                'invoicesales_id' => $invoiceId,
                'price' => $requestedAmount, // Use the requested price or total price
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
    
    // Display the specified payment order
    public function show($id)
    {
        $paymentOrder = PaymentOrder::with(['customer', 'paymentDetails.salesInvoice', 'journal'])->findOrFail($id);

        // Calculate the total price from payment details
        $totalPrice = $paymentOrder->paymentDetails->sum(function ($detail) {
            return $detail->price;
        });

        $journal = Journal::where('ref_id', $paymentOrder->id)->first();
    
        // Fetch postings related to the journal
        $postings = $journal ? $journal->postings : collect();
    
        // Map postings to get the Chart of Account
        $coas = $postings->map(function ($posting) {
            return $posting->account; // Adjust if necessary for your relationship
        });
        
        // dd($paymentOrder, $totalPrice);
        // Return the view with the payment order and its details
        return view('layouts.transactional.payment_order.show', [
            'paymentOrder' => $paymentOrder,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,
            'coa' => $coas,
        ]);
    }

    // Show the form for editing the specified payment order
    public function edit($id)
    {
        // Fetch the payment order and its details
        $paymentOrder = PaymentOrder::with('paymentDetails')->findOrFail($id);
        $customers = $paymentOrder->customer;

        $journal = Journal::where('ref_id', $paymentOrder->id)->first();
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
        $salesInvoices = SalesInvoice::where('customer_id', $customers->id)
            ->whereIn('status', ['pending', 'completed'])
            ->get();
    
        // Prepare combined details and filter out invoices with remaining price 0
        $combinedDetails = $salesInvoices->map(function ($invoice) use ($paymentOrder) {
        // Find the corresponding payment detail for this invoice
        $paymentDetail = $paymentOrder->paymentDetails->firstWhere('invoicesales_id', $invoice->id);

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
        return view('layouts.transactional.payment_order.edit', [
            'paymentOrder' => $paymentOrder,
            'customers' => $customers, // Use singular for clarity
            'combinedDetails' => $combinedDetails,
            'payment_order_id' => $paymentOrder->id, // Send payment order ID
            'payment_order_code' => $paymentOrder->code, // Send payment order code
            'payment_type' => $paymentType
        ]);
    }
    

    // Update the specified payment order in storage
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
            'payment_order_id' => 'required|exists:mstr_payment,id',
            'customer_id' => 'required|exists:mstr_customer,id', // Validate customer ID
            'description' => 'nullable|string|max:255', // Optional description
            'date' => 'required|date', // Validate date
            'requested.*' => 'required|numeric|min:0', // Validate requested amounts
            'invoice_id' => 'required|array', // Validate that invoice IDs are an array
            'invoice_id.*' => 'exists:invoice_sales,id', // Validate each invoice ID exists
            'payment_type' => 'required',
        ]);
        $paymentOrder = PaymentOrder::findOrFail($request->payment_order_id); // Find payment order or fail

        $paymentOrder->update([
            'description' => $request->description,
        ]);
        
        $orderDetails = $paymentOrder->paymentDetails;

        $journal = Journal::where('ref_id', $paymentOrder->id)->first();

        
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
                $paymentType = 3;
            }else{
                $paymentType = 1;
            }


            $firstRun = true; // Initialize a flag to track the first run
            foreach ($postings as $posting) {
                if ($firstRun) {
                    // Set to a positive amount on the first run
                    $posting->amount = abs($totalNewAmount);
                    $posting->account_id = $paymentType;
                } else {
                    // Set to a negative amount on the second run
                    $posting->amount = -abs($totalNewAmount);
                }
        
                $posting->save(); // Save each posting after updating
                $firstRun = false; // Toggle flag after the first iteration
            }
        }
        

        foreach($orderDetails as $detail)
        {
            $invoice = SalesInvoice::with('details')->findOrFail($detail->invoicesales_id);

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
            $paymentOrder->paymentDetails()->updateOrCreate(
                ['invoicesales_id' => $invoiceId],
                [
                    'price' => $request->requested[$index],
                    'status' => 'pending',
                    'payment_id' => $paymentOrder->id, // Insert the payment_order_id here
                ]
            );

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
        
        
        return redirect()->route('payment_order.show', $paymentOrder->id)->with('success', 'Payment Order updated successfully.');
    }

    // Remove the specified payment order from storage
    public function destroy($id)
    {
        // Find the payment order or fail
        $paymentOrder = PaymentOrder::findOrFail($id);
        $journal = Journal::where('ref_id', $paymentOrder->id)->first();
    
        // Loop through the payment order details and set their status to 'deleted'
        foreach ($paymentOrder->paymentDetails as $detail) {
            // Retrieve the related sales invoice
            $invoice = SalesInvoice::with('details')->findOrFail($detail->invoicesales_id); // Assuming invoicesales_id is the related field
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
