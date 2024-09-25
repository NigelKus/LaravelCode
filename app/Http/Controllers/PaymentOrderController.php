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
        dd($request->all());
        // Validate the request data

        

        $request->validate([
            'customer_id' => 'required|exists:customers,id', // Validate customer ID
            'description' => 'nullable|string|max:255', // Optional description
            'date' => 'required|date', // Validate date
            'requested.*' => 'required|numeric|min:0', // Validate requested amounts
            'invoice_id' => 'required|array', // Validate that invoice IDs are an array
            'invoice_id.*' => 'exists:sales_invoices,id', // Validate each invoice ID exists
        ]);

        $salesPaymentCode = CodeFactory::generatePaymentOrderCode();
        // Create a new payment order
        $paymentOrder = PaymentOrder::create([
            'code' => $salesPaymentCode,
            'customer_id' => $request['customer_id'],
            'description' => $request['description'],
            'date' => $request['date'],
            'status' => 'pending'
        ]);
        
        
        // Loop through the requested amounts and associate them with the created payment order
        foreach ($request['invoice_id'] as $index => $invoiceId) {
            $paymentOrder->invoiceLines()->create([
                'invoice_id' => $invoiceId,
                'requested' => $request['requested'][$index],
                // You may want to add more fields depending on your setup
            ]);
        }
        
        return redirect()->route('payment_orders.index')->with('success', 'Payment Order created successfully.');
    }
    

    // Display the specified payment order
    public function show($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        return view('payment_orders.show', compact('paymentOrder')); // Adjust the view as necessary
    }

    // Show the form for editing the specified payment order
    public function edit($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        return view('payment_orders.edit', compact('paymentOrder')); // Adjust the view as necessary
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
        return redirect()->route('payment_orders.index')->with('success', 'Payment Order updated successfully.');
    }

    // Remove the specified payment order from storage
    public function destroy($id)
    {
        $paymentOrder = PaymentOrder::findOrFail($id); // Find payment order or fail
        $paymentOrder->delete(); // Soft delete the payment order
        return redirect()->route('payment_orders.index')->with('success', 'Payment Order deleted successfully.');
    }

    public function fetchInvoices($customerId)
{
    $invoices = SalesInvoice::where('customer_id', $customerId)->get(['id']); // Adjust the fields as necessary
    return response()->json(['invoices' => $invoices]);
}

}
