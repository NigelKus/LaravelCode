<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Posting;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\ChartOfAccount;
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
        $statuses = ['pending', 'completed']; // Define your statuses
        $purchaseOrders = PurchaseOrder::all(); // Fetch all purchase orders for the filter

        $query = PurchaseInvoice::with(['supplier' => function($q){
            $q->withTrashed();
        }])->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);


        $query->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);

        // Apply status filter if present
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        // Apply code search filter if present
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        if ($request->has('purchase_order') && $request->purchase_order != '') {
            $query->where('code', 'like', '%' . $request->purchase_order . '%');
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
        $purchaseInvoices = $query->paginate($perPage);

        foreach ($purchaseInvoices as $invoice) {
            // Calculate total price of the invoice details
            $totalPrice = $invoice->details->sum(function($detail) {
                return $detail->price * $detail->quantity; // Ensure quantity exists
            });
        
            // Add the calculated fields to the invoice object
            $invoice->total_price = $totalPrice;
         // Adjust this if necessary // Accessor will automatically calculate it
        }
    
        return view('layouts.transactional.purchase_invoice.index', [
            'purchaseInvoices' => $purchaseInvoices,
            'statuses' => $statuses,
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    public function create()
    {
        // Fetch only active suppliers
        $suppliers = Supplier::where('status', 'active')->get();
    
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Fetch all purchase orders to populate the purchase order dropdown
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
        // Fetch the invoice and its details
        $invoice = PurchaseInvoice::with('details')->findOrFail($id);
        
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
    
                    
    public function getPurchaseInvoicesBySupplier($supplierId)
    {
        // Fetch purchase invoices for the selected supplier, including their details
        $purchaseInvoices = PurchaseInvoice::with('details')
            ->where('supplier_id', $supplierId)
            ->where('status', 'pending')
            ->get();
    
        // Initialize an empty collection for valid invoices
        $validInvoices = collect();

        // Process each invoice to calculate total and remaining price
        $purchaseInvoices->each(function ($invoice) use ($validInvoices) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });
            $invoice->remaining_price = $invoice->calculatePriceRemaining();
            
            // Only add invoices with a remaining price greater than 0
            if ($invoice->remaining_price > 0) {
                $validInvoices->push($invoice);
            }
        });
        return response()->json(['purchaseInvoices' => $validInvoices]);
    }

    public function store(Request $request)
    {   
        
        $filteredData = collect($request->input('requested'))->filter(function ($value, $key) {
            return $value > 0; // Keep only values greater than 0
        })->keys()->toArray();

        // Now filter other related fields to keep the same indexes
        $requestData = $request->all();
        foreach (['requested', 'qtys', 'price_eachs', 'price_totals', 'purchase_order_detail_ids'] as $field) {
            $requestData[$field] = array_intersect_key($requestData[$field], array_flip($filteredData));
        }

        $requestData['price_eachs'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_eachs'], array_flip($filteredData)));
        $requestData['price_totals'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_totals'], array_flip($filteredData)));

        // Now validate the filtered data
        $request->replace($requestData);
        
        $request->validate([
            'supplier_id' => 'required|exists:mstr_supplier,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'purchaseorder_id' => 'required|exists:purchase_order,id',
            'requested.*' => 'required|integer|min:1', // Validate requested quantity
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
            $purchaseInvoice->code = $purchaseInvoiceCode; // Set the generated code
            $purchaseInvoice->purchaseorder_id = $request->input('purchaseorder_id');
            $purchaseInvoice->supplier_id = $request->input('supplier_id');
            $purchaseInvoice->description = $request->input('description');
            $purchaseInvoice->date = $request->input('date');
            $purchaseInvoice->due_date = $request->input('due_date');
            $purchaseInvoice->status = 'pending'; // Assuming a default status
            $purchaseInvoice->save();
            
            // Get the purchase order ID from the request
            $purchaseOrderId = $request->input('purchaseorder_id');
            
            // Fetch the corresponding purchase order
            $existingpurchaseOrder = PurchaseOrder::with('details')->find($purchaseOrderId); // Eager load details
            if (!$existingpurchaseOrder) {
                throw new \Exception('purchase order not found.');
            }

            // Retrieve product details
            $requestedQuantities = $request->input('requested');
            $priceEaches = $request->input('price_eachs');
            $purchaseDetail = $request->input('purchase_order_detail_ids');
            // dd($purchaseDetail);
            foreach ($purchaseDetail as $index => $purchaseOrderDetailId) {
                $purchaseOrderDetail = $existingpurchaseOrder->details->where('id', $purchaseOrderDetailId)->first();

                if (!$purchaseOrderDetail) {
                    throw new \Exception('purchase order detail not found for ID ' . $purchaseOrderDetailId);
                }
                
                $productId = $purchaseOrderDetail->product_id;
                $requested = $requestedQuantities[$index] ?? 0;
                // dd($purchaseOrderDetailId);
                // $purchaseOrderDetailId = $purchaseOrderDetailIds[$index] ?? null;
                
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
        // Calculate total price from invoicedetails
        $totalPrice = $purchaseInvoice->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });

        $journal = Journal::where('ref_id', $purchaseInvoice->id)->first();
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
        ]);
    }

    public function edit($id)
    {
        // Fetch the purchase invoice with its details and related products
        $purchaseInvoice = purchaseInvoice::with(['details.product'])->findOrFail($id);
        
        // Convert dates to Carbon instances
        $purchaseInvoice->date = \Carbon\Carbon::parse($purchaseInvoice->date);
        $purchaseInvoice->due_date = \Carbon\Carbon::parse($purchaseInvoice->due_date);
        
        // Fetch related suppliers, products, and purchase orders
        $suppliers = supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $purchaseOrders = purchaseOrder::where('status', 'pending')->get();

        $purchaseOrder = purchaseOrder::findOrFail($purchaseInvoice->purchaseorder_id);
    
        // Check if the purchase order status is canceled or deleted
        if ($purchaseOrder->status === 'canceled' || $purchaseOrder->status === 'deleted') {
            return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->withErrors([
                'error' => 'The purchase order has already been deleted or canceled.'
            ]);
        }
        
        $purchaseOrderDetails = purchaseOrderDetail::where('purchaseorder_id', $purchaseInvoice->purchaseorder_id)->get();
        
        // Map purchase order details by product_id
        $purchaseOrderDetailsMap = $purchaseOrderDetails->keyBy('product_id');
        
        // Pass data to view
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
        
        // Update purchase invoice fields
        $purchaseInvoice->supplier_id = $request['supplier_id'];
        $purchaseInvoice->description = $request['description'];
        $purchaseInvoice->date = $request['date'];
        $purchaseInvoice->due_date = $request['due_date'];
        $purchaseInvoice->save();
        
        // Get existing purchase invoice details
        $purchaseInvoice = purchaseInvoice::with('details')->findOrFail($id);
        $invoiceDetails = $purchaseInvoice->details;

        $journal = Journal::where('ref_id', $purchaseInvoice->id)->first();

        $productIds = $request['product_id'];
        $requested = $request['requested'];
        $priceEachs = $request['price_eachs'];

        $priceEachsAcc = array_map(function($price) {
            return str_replace(',', '', $price); // Remove commas
        }, $priceEachs);

        if ($journal) {
            // Fetch postings related to this journal
            $postings = Posting::where('journal_id', $journal->id)->get();
            $totalNewAmount = 0;
        
            // Loop through requested items and calculate totalNewAmount
            foreach ($requested as $i => $quantity) {
                // Skip iteration if price or quantity is zero or empty
                if (!empty($priceEachsAcc[$i]) && $priceEachsAcc[$i] != 0 && $quantity != 0) {
                    $totalNewAmount += $priceEachsAcc[$i] * $quantity;
                }
            }
        
            $firstRun = true; // Initialize a flag to track the first run
            foreach ($postings as $posting) {
                if ($firstRun) {
                    // Set to a positive amount on the first run
                    $posting->amount = abs($totalNewAmount);
                } else {
                    // Set to a negative amount on the second run
                    $posting->amount = -abs($totalNewAmount);
                }
        
                $posting->save(); // Save each posting after updating
                $firstRun = false; // Toggle flag after the first iteration
            }
        }

        // // Delete each detail
        foreach ($invoiceDetails as $detail) {
            $detail->delete();
        }

        $requested = $request['requested'];
        $priceEachs = $request['price_eachs'];
        $productIds = $request['product_id'];
        $purchase_order_id = $request['purchase_order_id'];

        // dd($purchase_order_id);

        foreach ($productIds as $i => $productId) {
            if ($productId !== null && (!empty($requested[$i]) && (int)$requested[$i] > 0)) { // Check if product ID is not null
                // Create a new purchaseInvoiceDetail instance
                $purchaseInvoiceDetail = new purchaseInvoiceDetail();
                $purchaseInvoiceDetail->purchaseinvoice_id = $purchaseInvoice->id;
                $purchaseInvoiceDetail->product_id = $productId; // Product ID from the request
                $purchaseInvoiceDetail->quantity = $requested[$i] !== null ? (int)$requested[$i] : 0;
                $purchaseInvoiceDetail->purchasedetail_id = $request['purchasedetail_id'][$i] !== null ? (int)$request['purchasedetail_id'][$i] : null;
                $purchaseInvoiceDetail->price = $priceEachs[$i] !== null ? (float)str_replace(',', '', $priceEachs[$i]) : 0; // Price from the request
                $purchaseInvoiceDetail->status = 'pending';
                
                
                $purchaseInvoiceDetail->save();
                purchaseorderDetail::checkAndUpdateStatus($purchase_order_id[$i], $productId, $request['purchasedetail_id'][$i]);
            }
        }
        // Redirect or return response
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

        // Check if the purchase order status is canceled or deleted
        if ($purchaseOrder->status === 'canceled' || $purchaseOrder->status === 'deleted') {
            // Return an error message if the purchase order has been canceled or deleted
            return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->withErrors([
                'error' => 'The purchase order has already been deleted or canceled. Status cannot be updated.'
            ]);
        }

        $purchaseInvoice->status = $request->input('status');
        $purchaseInvoice->save();
        // Collection of details

        // Update the status of each associated invoice detail
        foreach ($purchaseInvoice->details as $detail) {
            $detail->status = $request->input('status'); // Set the same status as the invoice
            $detail->save(); // Save each detail
        }
    
        return redirect()->route('purchase_invoice.show', $purchaseInvoice->id)->with('success', 'purchase invoice updated successfully.');
    }
    

    public function destroy($id)
    {
        // Find the purchase invoice or fail if not found
        $purchaseInvoice = purchaseInvoice::findOrFail($id);

        $journal = Journal::where('ref_id', $purchaseInvoice->id)->first();

        $hasPaymentOrderDetail = false;

        // Check if any payment order detail has the same purchase invoice ID
        foreach ($purchaseInvoice->details as $detail) {
            // Check if there is a PaymentOrderDetail with the same purchase_invoice_id
            $paymentOrderDetail = \App\Models\PaymentPurchaseDetail::where('invoicepurchase_id', $purchaseInvoice->id)->exists();
        
            if ($paymentOrderDetail) {
                $hasPaymentOrderDetail = true;
                break; // Exit the loop if a match is found
            }
        }
        
        if ($hasPaymentOrderDetail) {
            return redirect()->back()->withErrors(['error' => 'There is an ongoing payment related to this purchase invoice.']);
        }
        
        foreach ($purchaseInvoice->details as $detail) {
            try {
                // Find the corresponding purchase order detail using an appropriate relation
                $purchaseOrderDetail = $detail->purchaseOrderDetail; // Adjust this line as necessary

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
                
                // Call the adjustQuantityRemaining method on the purchaseOrderDetail
                $purchaseOrderDetail->adjustQuantityRemaining($detail->quantity); // Adjust the quantity sent
                $detail->update([
                    'status' => 'deleted',
                ]);
                
                $detail->delete(); // Update status of detail

                if ($purchaseOrderDetail->status === 'completed') {
                    $purchaseOrderDetail->update(['status' => 'pending']);
    
                    // Retrieve the associated purchase order and update its status if necessary
                    $purchaseOrder = $purchaseOrderDetail->purchaseOrder; // Adjust this line as necessary
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
    
        // Redirect back to the purchase invoice index with a success message
        return redirect()->route('purchase_invoice.index')->with('success', 'purchase Invoice deleted successfully.');
    }
}