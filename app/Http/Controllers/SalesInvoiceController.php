<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
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
        $statuses = ['pending', 'completed']; // Define your statuses
        $salesOrders = SalesOrder::all(); // Fetch all sales orders for the filter
        $query = SalesInvoice::with('details', 'customer', 'salesOrder'); // Eager load the relationships


        $query->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        // Apply status filter if present
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        // Apply code search filter if present
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        if ($request->has('sales_order') && $request->sales_order != '') {
            $query->where('code', 'like', '%' . $request->sales_order . '%');
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
        $salesInvoices = $query->paginate($perPage);

        foreach ($salesInvoices as $invoice) {
            // Calculate total price of the invoice details
            $totalPrice = $invoice->details->sum(function($detail) {
                return $detail->price * $detail->quantity; // Ensure quantity exists
            });
        
            // Add the calculated fields to the invoice object
            $invoice->total_price = $totalPrice;
         // Adjust this if necessary // Accessor will automatically calculate it
        }
    
        return view('layouts.transactional.sales_invoice.index', [
            'salesInvoices' => $salesInvoices,
            'statuses' => $statuses,
            'salesOrders' => $salesOrders
        ]);
    }

    public function create()
    {
        // Fetch only active customers
        $customers = Customer::where('status', 'active')->get();
    
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Fetch all sales orders to populate the sales order dropdown
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
        
        $filteredData = collect($request->input('requested'))->filter(function ($value, $key) {
            return $value > 0; // Keep only values greater than 0
        })->keys()->toArray();

        // Now filter other related fields to keep the same indexes
        $requestData = $request->all();
        foreach (['requested', 'qtys', 'price_eachs', 'price_totals', 'sales_order_detail_ids'] as $field) {
            $requestData[$field] = array_intersect_key($requestData[$field], array_flip($filteredData));
        }

        $requestData['price_eachs'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_eachs'], array_flip($filteredData)));
        $requestData['price_totals'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_totals'], array_flip($filteredData)));

        // Now validate the filtered data
        $request->replace($requestData);
        
        $request->validate([
            'customer_id' => 'required|exists:mstr_customer,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'salesorder_id' => 'required|exists:mstr_salesorder,id',
            'requested.*' => 'required|integer|min:1', // Validate requested quantity
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
            $salesInvoice->code = $salesInvoiceCode; // Set the generated code
            $salesInvoice->salesorder_id = $request->input('salesorder_id');
            $salesInvoice->customer_id = $request->input('customer_id');
            $salesInvoice->description = $request->input('description');
            $salesInvoice->date = $request->input('date');
            $salesInvoice->due_date = $request->input('due_date');
            $salesInvoice->status = 'pending'; // Assuming a default status
            $salesInvoice->save();
            
            // Get the sales order ID from the request
            $salesOrderId = $request->input('salesorder_id');
            
            // Fetch the corresponding sales order
            $existingSalesOrder = SalesOrder::with('details')->find($salesOrderId); // Eager load details
            if (!$existingSalesOrder) {
                throw new \Exception('Sales order not found.');
            }

            // Retrieve product details
            $requestedQuantities = $request->input('requested');
            $priceEaches = $request->input('price_eachs');
            $salesDetail = $request->input('sales_order_detail_ids');
            // dd($salesDetail);
            foreach ($salesDetail as $index => $salesOrderDetailId) {
                $salesOrderDetail = $existingSalesOrder->details->where('id', $salesOrderDetailId)->first();

                if (!$salesOrderDetail) {
                    throw new \Exception('Sales order detail not found for ID ' . $salesOrderDetailId);
                }
                
                $productId = $salesOrderDetail->product_id;
                $requested = $requestedQuantities[$index] ?? 0;
                // dd($salesOrderDetailId);
                // $salesOrderDetailId = $salesOrderDetailIds[$index] ?? null;
                
                $salesInvoiceDetail = new SalesInvoiceDetail();
                $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
                $salesInvoiceDetail->product_id = $productId;
                $salesInvoiceDetail->quantity = $requested;
                $salesInvoiceDetail->salesdetail_id = $salesOrderDetail->id;
                $salesInvoiceDetail->price = $priceEaches[$index];
                $salesInvoiceDetail->status = 'pending'; 
                $salesInvoiceDetail->save();

                SalesorderDetail::checkAndUpdateStatus($salesOrderId, $productId, $salesOrderDetailId);
                
            }
            
            
            AE_S02_FinishSalesInvoice::process($salesInvoice);


            // Commit the transaction
            DB::commit();

            return redirect()->route('sales_invoice.show', $salesInvoice->id)
            ->with('success', 'Sales invoice updated successfully.');
    }


    public function show($id)
    {
        // Fetch the sales invoice with customer, details including related products, and invoicedetails
        $salesInvoice = SalesInvoice::with(['customer', 'details.product', 'salesOrder', 'journal'])->findOrFail($id);
        
        // Calculate total price from invoicedetails
        $totalPrice = $salesInvoice->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        
        // Get the journal where ref_id matches the sales invoice ID
        $journal = Journal::where('ref_id', $salesInvoice->id)->first();
    
        // Fetch postings related to the journal
        $postings = $journal ? $journal->postings : collect();
    
        // Map postings to get the Chart of Account
        $coas = $postings->map(function ($posting) {
            return $posting->account; // Adjust if necessary for your relationship
        });
    
        // Return the view with the sales invoice and its details
        return view('layouts.transactional.sales_invoice.show', [
            'salesInvoice' => $salesInvoice,
            'totalPrice' => $totalPrice,
            'journal' => $journal,
            'postings' => $postings,
            'coa' => $coas,
        ]);
    }
    

    public function edit($id)
    {
        // Fetch the sales invoice with its details and related products
        $salesInvoice = SalesInvoice::with(['details.product'])->findOrFail($id);
        
        // Convert dates to Carbon instances
        $salesInvoice->date = \Carbon\Carbon::parse($salesInvoice->date);
        $salesInvoice->due_date = \Carbon\Carbon::parse($salesInvoice->due_date);
        
        // Fetch related customers, products, and sales orders
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $salesOrders = SalesOrder::where('status', 'pending')->get();

        $salesOrder = SalesOrder::findOrFail($salesInvoice->salesorder_id);
    
        // Check if the sales order status is canceled or deleted
        if ($salesOrder->status === 'canceled' || $salesOrder->status === 'deleted') {
            // Return an error message if the sales order has been canceled or deleted
            return redirect()->route('sales_invoice.show', $salesInvoice->id)->withErrors([
                'error' => 'The sales order has already been deleted or canceled.'
            ]);
        }
        
        $salesOrderDetails = SalesOrderDetail::where('salesorder_id', $salesInvoice->salesorder_id)->get();
        
        // Map sales order details by product_id
        $salesOrderDetailsMap = $salesOrderDetails->keyBy('product_id');
        
        // Pass data to view
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
        $salesInvoice = SalesInvoice::findOrFail($id);
        
        // Update sales invoice fields
        $salesInvoice->customer_id = $request['customer_id'];
        $salesInvoice->description = $request['description'];
        $salesInvoice->date = $request['date'];
        $salesInvoice->due_date = $request['due_date'];
        $salesInvoice->save();
        
        // Get existing sales invoice details
        $salesInvoice = SalesInvoice::with('details')->findOrFail($id);
        $invoiceDetails = $salesInvoice->details;

        // // Delete each detail
        foreach ($invoiceDetails as $detail) {
            $detail->delete();
        }

        $requested = $request['requested'];
        $priceEachs = $request['price_eachs'];
        $productIds = $request['product_id'];
        $sales_order_id = $request['sales_order_id'];

        // dd($sales_order_id);

        foreach ($productIds as $i => $productId) {
            if ($productId !== null && (!empty($requested[$i]) && (int)$requested[$i] > 0)) { // Check if product ID is not null
                // Create a new SalesInvoiceDetail instance
                $salesInvoiceDetail = new SalesInvoiceDetail();
                $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
                $salesInvoiceDetail->product_id = $productId; // Product ID from the request
                $salesInvoiceDetail->quantity = $requested[$i] !== null ? (int)$requested[$i] : 0;
                $salesInvoiceDetail->salesdetail_id = $request['salesdetail_id'][$i] !== null ? (int)$request['salesdetail_id'][$i] : null;
                $salesInvoiceDetail->price = $priceEachs[$i] !== null ? (float)str_replace(',', '', $priceEachs[$i]) : 0; // Price from the request
                $salesInvoiceDetail->status = 'pending';
                
                
                $salesInvoiceDetail->save();
                SalesorderDetail::checkAndUpdateStatus($sales_order_id[$i], $productId, $request['salesdetail_id'][$i]);
            }
        }
        // Redirect or return response
        return redirect()->route('sales_invoice.show', $salesInvoice->id)
            ->with('success', 'Sales invoice updated successfully.');
    }

    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled,deleted',
        ]);
        
        $salesInvoice = SalesInvoice::findOrFail($id);

        $salesOrder = SalesOrder::findOrFail($salesInvoice->salesorder_id);

        // Check if the sales order status is canceled or deleted
        if ($salesOrder->status === 'canceled' || $salesOrder->status === 'deleted') {
            // Return an error message if the sales order has been canceled or deleted
            return redirect()->route('sales_invoice.show', $salesInvoice->id)->withErrors([
                'error' => 'The sales order has already been deleted or canceled. Status cannot be updated.'
            ]);
        }

        $salesInvoice->status = $request->input('status');
        $salesInvoice->save();
        // Collection of details

        // Update the status of each associated invoice detail
        foreach ($salesInvoice->details as $detail) {
            $detail->status = $request->input('status'); // Set the same status as the invoice
            $detail->save(); // Save each detail
        }
    
        return redirect()->route('sales_invoice.show', $salesInvoice->id)->with('success', 'Sales invoice updated successfully.');
    }
    

    public function destroy($id)
    {
        // Find the sales invoice or fail if not found
        $salesInvoice = SalesInvoice::findOrFail($id);

        $hasPaymentOrderDetail = false;

        // Check if any payment order detail has the same sales invoice ID
        foreach ($salesInvoice->details as $detail) {
            // Check if there is a PaymentOrderDetail with the same sales_invoice_id
            $paymentOrderDetail = \App\Models\PaymentOrderDetail::where('invoicesales_id', $salesInvoice->id)->exists();
        
            if ($paymentOrderDetail) {
                $hasPaymentOrderDetail = true;
                break; // Exit the loop if a match is found
            }
        }
        
        if ($hasPaymentOrderDetail) {
            return redirect()->back()->withErrors(['error' => 'There is an ongoing payment related to this sales invoice.']);
        }
        
        foreach ($salesInvoice->details as $detail) {
            try {
                // Find the corresponding sales order detail using an appropriate relation
                $salesOrderDetail = $detail->salesOrderDetail; // Adjust this line as necessary
                
                // Call the adjustQuantityRemaining method on the SalesOrderDetail
                $salesOrderDetail->adjustQuantityRemaining($detail->quantity); // Adjust the quantity sent
                $detail->update([
                    'status' => 'deleted',
                ]);
                
                $detail->delete(); // Update status of detail

                if ($salesOrderDetail->status === 'completed') {
                    $salesOrderDetail->update(['status' => 'pending']);
    
                    // Retrieve the associated sales order and update its status if necessary
                    $salesOrder = $salesOrderDetail->salesOrder; // Adjust this line as necessary
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
    
        // Redirect back to the sales invoice index with a success message
        return redirect()->route('sales_invoice.index')->with('success', 'Sales Invoice deleted successfully.');
    }
    
    public function getInvoiceDetails($id)
    {
        // Fetch the invoice and its details
        $invoice = SalesInvoice::with('details')->findOrFail($id);
        
        // Calculate the total price of the invoice details
        $totalPrice = $invoice->details->sum(function($detail) {
            return $detail->price * $detail->quantity; // Ensure you have a quantity field in your details
        });
    
        // Get the price remaining using the accessor
        $priceRemaining = $invoice->price_remaining; // Use the accessor for price remaining
    
        return response()->json([
            'total_price' => $totalPrice,
            'price_remaining' => $priceRemaining,
        ]);
    }
    
    
    public function getSalesInvoicesByCustomer($customerId)
    {
        // Fetch sales invoices for the selected customer, including their details
        $salesInvoices = SalesInvoice::with('details')
            ->where('customer_id', $customerId)
            ->where('status', 'pending')
            ->get();
    
        // Initialize an empty collection for valid invoices
        $validInvoices = collect();
    
        // Process each invoice to calculate total and remaining price
        $salesInvoices->each(function ($invoice) use ($validInvoices) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });
            $invoice->remaining_price = $invoice->calculatePriceRemaining();

            
            // Only add invoices with a remaining price greater than 0
            if ($invoice->remaining_price > 0) {
                $validInvoices->push($invoice);
            }
        });
    
        return response()->json(['salesInvoices' => $validInvoices]);
    }
}