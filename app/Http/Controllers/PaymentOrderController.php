<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\SalesInvoiceDetail;
use Database\Factories\CodeFactory;
use App\Models\PaymentOrder; // Ensure to import your model

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
        ]);
        
        // dd($request->all());
        // Generate the payment order code
        $salesPaymentCode = CodeFactory::generatePaymentOrderCode();
    
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
            $paymentOrder->paymentDetails()->create([
                'payment_id' => $paymentOrder->id,
                'invoicesales_id' => $invoiceId,
                'price' => $request['requested'][$index],
                'status' => 'pending',// Add remaining_price if necessary
            ]);
        }
    
        return redirect()->route('payment_order.index')->with('success', 'Payment Order created successfully.');
    }
    
    

    // Display the specified payment order
    public function show($id)
    {
        $paymentOrder = PaymentOrder::with(['customer', 'paymentDetails.salesInvoice'])->findOrFail($id);

        // Calculate the total price from payment details
        $totalPrice = $paymentOrder->paymentDetails->sum(function ($detail) {
            return $detail->price;
        });
        
        dd($paymentOrder, $totalPrice);
        // Return the view with the payment order and its details
        return view('layouts.transactional.payment_order.show', [
            'paymentOrder' => $paymentOrder,
            'totalPrice' => $totalPrice,
        ]);
    }

    // Show the form for editing the specified payment order
    public function edit($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        return view('payment_order.edit', compact('paymentOrder')); // Adjust the view as necessary
    }

    // Update the specified payment order in storage
    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            // Add your validation rules
        ]);

        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        $paymentOrder->update($validatedData); // Update the payment order
        return redirect()->route('payment_order.index')->with('success', 'Payment Order updated successfully.');
    }

    // Remove the specified payment order from storage
    public function destroy($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        $paymentOrder->delete(); // Soft delete the payment order
        return redirect()->route('payment_order.index')->with('success', 'Payment Order deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        // $request->validate([
        //     'status' => 'required|in:pending,completed,cancelled,deleted',
        // ]);
        
        $paymentOrder = PaymentOrder::findOrFail($id);

        // $salesOrder = SalesOrder::findOrFail($salesInvoice->salesorder_id);

        // // Check if the sales order status is canceled or deleted
        // if ($salesOrder->status === 'canceled' || $salesOrder->status === 'deleted') {
        //     // Return an error message if the sales order has been canceled or deleted
        //     return redirect()->route('sales_invoice.show', $salesInvoice->id)->withErrors([
        //         'error' => 'The sales order has already been deleted or canceled. Status cannot be updated.'
        //     ]);
        // }

        // $salesInvoice->status = $request->input('status');
        // $salesInvoice->save();
        // // Collection of details

        // // Update the status of each associated invoice detail
        // foreach ($salesInvoice->details as $detail) {
        //     $detail->status = $request->input('status'); // Set the same status as the invoice
        //     $detail->save(); // Save each detail
        // }
    
        return redirect()->route('payment_order.show', $paymentOrder->id)->with('success', 'Sales invoice updated successfully.');
    }

        public function fetchInvoices($customerId)
    {
        $invoices = SalesInvoice::where('customer_id', $customerId)->get(['id']); // Adjust the fields as necessary
        return response()->json(['invoices' => $invoices]);
    }

}
