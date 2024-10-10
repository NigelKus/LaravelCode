<?php

namespace App\Http\Controllers;

use Database\Factories\CodeFactory; 
use App\Models\SalesorderDetail;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; // Define your statuses
        $query = SalesOrder::query();

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
        $salesOrders = $query->paginate($perPage);
    
        return view('layouts.transactional.sales_order.index', compact('salesOrders', 'statuses'));
    }
    
    public function create()
    {
        // Fetch only active customers
        $customers = Customer::where('status', 'active')->get();
        
        dd($customers);
        
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Return the view with the customers and products data
        return view('layouts.transactional.sales_order.create-copy', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $productIds = $request->input('product_ids', []);
        $quantities = $request->input('qtys', []);
        $prices = $request->input('price_eachs', []);
        $priceTotals = $request->input('price_totals', []);
        
        // Create a filtered array that keeps only non-null product IDs and their corresponding values
        $filteredData = array_filter(array_map(null, $productIds, $quantities, $prices, $priceTotals), function($item) {
            return $item[0] !== null; // Check if product ID is not null
        });
        
        // Unpack the filtered data into separate arrays
        $filteredProductIds = array_column($filteredData, 0);
        $filteredQuantities = array_column($filteredData, 1);
        $filteredPrices = array_column($filteredData, 2);
        $filteredPriceTotals = array_column($filteredData, 3);
        
        if (empty($filteredProductIds)) {
            return redirect()->back()->withErrors(['error' => 'At least one product must be selected.']);
        }

        // Remove commas from prices and totals
        $filteredPrices = array_map(fn($value) => str_replace(',', '', $value), $filteredPrices);
        $filteredPriceTotals = array_map(fn($value) => str_replace(',', '', $value), $filteredPriceTotals);
        
        // Prepare the request data for validation
        $request->merge([
            'product_ids' => array_values($filteredProductIds),
            'qtys' => array_values($filteredQuantities),
            'price_eachs' => array_values($filteredPrices),
            'price_totals' => array_values($filteredPriceTotals),
        ]);
        
        // Dump and die to inspect the merged data
        // dd($request->all());    
        
        // dd($request->all());
        // Validate the incoming request data
        $validatedData = $request->validate([
            'customer_id' => 'required|integer|exists:mstr_customer,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:mstr_product,id',
            'qtys' => 'required|array',
            'qtys.*' => 'required|integer|min:1',
            'price_eachs' => 'required|array',
            'price_eachs.*' => 'required|numeric|min:0',
        ]);
        // dd($validatedData);
        // Generate the sales order code using CodeFactory
        $salesOrderCode = CodeFactory::generateSalesOrderCode();
    
        // Begin a database transaction for both SalesOrder and SalesOrderDetail
        DB::beginTransaction();
        // Create a new sales order record
        $salesOrder = SalesOrder::create([  
            'code' => $salesOrderCode,
            'customer_id' => $validatedData['customer_id'],
            'description' => $validatedData['description'],
            'status' => 'pending',
            'date' => $validatedData['date'],
        ]);
        
        // dd($salesOrder);

        // Get product details from the validated request data
        $productIds = $validatedData['product_ids'];
        $quantities = $validatedData['qtys'];
        $prices = $validatedData['price_eachs'];
    
        // Check if the arrays have the same length
        if (count($productIds) !== count($quantities) || count($productIds) !== count($prices)) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Mismatch between product details.']);
        }
    
        // Prepare an array to collect all detail data
        $detailsData = [];
    
        // Collect product details and filter out invalid entries
        foreach ($productIds as $index => $productId) {
            $quantity = $quantities[$index];
            $price = $prices[$index];
    
            // Add validated entry to the array
            $detailsData[] = [
                'salesorder_id' => $salesOrder->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'pending',
            ];
        }
        
        // DD($detailsData);
        // Insert products into the salesorder_detail table in a batch
        SalesorderDetail::insert($detailsData);
    
        // Commit the transaction
        DB::commit();
    
        // Redirect with success message
        return redirect()->route('sales_order.show', ['id' => $salesOrder->id])
            ->with('success', 'Sales Order created successfully.')
            ->with('sales_order_code', $salesOrderCode);
    }
    
    
    public function show($id)
    {
        // Fetch the sales order and its details
        $salesOrder = SalesOrder::with('customer', 'details.product')->findOrFail($id);

        
        // Calculate total price
        $totalPrice = $salesOrder->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        
        // Return the view with the sales order and its details
        return view('layouts.transactional.sales_order.show', compact('salesOrder', 'totalPrice'));
    }

        public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $salesOrder = SalesOrder::findOrFail($id);
        $salesOrder->status = $request->input('status');
        $salesOrder->save();

        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Status updated successfully.');
        
    }
    
        public function edit($id)
    {
        // Fetch the sales order with details
        $salesOrder = SalesOrder::with('details.product')->findOrFail($id);

        // Convert date to Carbon object if it's not already
        $salesOrder->date = \Carbon\Carbon::parse($salesOrder->date);

        // Fetch customers and products
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('layouts.transactional.sales_order.edit', compact('salesOrder', 'customers', 'products'));
    }


    // Update method
    public function update(Request $request, $id)
    {

        $salesOrder = SalesOrder::findOrFail($id);

        // Get the arrays from the request
        $product_ids = $request->input('product_ids', []);
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
    
        // Filter out null values
        $filteredData = array_filter(array_map(null, $product_ids, $qtys, $price_eachs, $price_totals), function($item) {
            return !is_null($item[0]); // Check the product_id
        });
    
        // Check if any product_id is null after filtering
        if (empty($filteredData)) {
            return redirect()->back()->withErrors(['product_ids' => 'There is a missing product.'])->withInput();
        }
    
        // Prepare sales order details
        $salesOrderDetails = [];
        foreach ($filteredData as $data) {
            list($product_id, $qty, $price_each, $price_total) = $data;
    
            $salesOrderDetails[] = [
                'product_id' => $product_id,
                'quantity' => !is_null($qty) ? (int)$qty : null,
                'price' => !is_null($price_each) ? (float)str_replace(',', '', $price_each) : null,
                'price_total' => !is_null($price_total) ? (float)str_replace(',', '', $price_total) : null,
            ];
        }
        
        // Update the sales order
        $salesOrder->update([
            'customer_id' => $request->input('customer_id'),
            'description' => $request->input('description'),
            'date' => \Carbon\Carbon::parse($request->input('date')),
        ]);
        
        // Get the arrays from the request
        $product_ids = $request->input('product_ids', []);
        
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
    
        // Initialize an empty array to hold the combined details
        $salesOrderDetails = [];
    
        // Determine the number of items in each array
        $length = count($product_ids);
    
        for ($i = 0; $i < $length; $i++) {
            // Only add to the details array if the product_id is not null
            if (!is_null($product_ids[$i])) {
                $salesOrderDetails[] = [
                    'product_id' => $product_ids[$i],
                    'quantity' => !is_null($qtys[$i]) ? (int)$qtys[$i] : null,
                    'price' => !is_null($price_eachs[$i]) ? (float)str_replace(',', '', $price_eachs[$i]) : null,
                    'price_total' => !is_null($price_totals[$i]) ? (float)str_replace(',', '', $price_totals[$i]) : null,
                ];
            }
        }
    
        // Dump the combined array to inspect
        // dd($salesOrderDetails);
        
        // Update existing details and insert new ones
        foreach ($salesOrderDetails as $detail) {
            SalesOrderDetail::updateOrCreate(
                [
                    'salesorder_id' => $salesOrder->id,
                    'product_id' => $detail['product_id']
                ],
                [
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'price_total' => $detail['price_total'],
                    'status' => 'pending',
                ]
            );
        }
        // dd($salesOrderDetails);
        // Delete details that are no longer in the $salesOrderDetails array
        SalesOrderDetail::where('salesorder_id', $salesOrder->id)
            ->whereNotIn('product_id', array_column($salesOrderDetails, 'product_id'))
            ->delete();

        // Redirect to the sales orders index page with a success message
        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Sales Order updated successfully.');
    }

// SalesOrderController.php
// app/Http/Controllers/SalesOrderController.php
    public function getProducts($salesOrderId)
    {
        try {
            // Find the sales order and eager load the details with their associated product
            $salesOrder = SalesOrder::with(['details' => function ($query) {
                // Filter details to only include those with status "pending"
                $query->where('status', 'pending');
            }, 'details.product'])->find($salesOrderId);
            
            
            if (!$salesOrder) {
                throw new \Exception('Sales order not found');
            }
            
            // Map the details to the required format
                $productsData = $salesOrder->details->map(function ($detail) {
                // Use the accessor to get the remaining quantity
                $remainingQuantity = $detail->quantity_remaining ?? 0; // Default value if null
                
                return [
                    'product_id' => $detail->id, //product id
                    'code' => $detail->product->code,
                    'quantity' => $detail->quantity, // Total quantity requested
                    'price' => $detail->price,
                    'requested' => $detail->quantity, // Quantity requested
                    'remaining_quantity' => $remainingQuantity,
                    'sales_order_detail_id' => $detail->sales_order_detail_id // Corrected key name
                ];
            });
            
            return response()->json(['products' => $productsData]);
        
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            
            // If you want to return an error response instead of logging it
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function destroy($id)
    {
        // Find the sales order or fail if not found
        $salesOrder = SalesOrder::findOrFail($id);

        // Initialize a flag to track if any product fails the check
        $hasInsufficientQuantity = false;
    
        // Check each product in the sales order details
        foreach ($salesOrder->details as $detail) {
            // You can debug here if needed
            // dd($detail->quantity_remaining, $detail->quantity);
            
            if ($detail->quantity_remaining < $detail->quantity) {
                $hasInsufficientQuantity = true;
                break; // Exit the loop if one fails the check
            }
        }
    
        if ($hasInsufficientQuantity) {
            return redirect()->back()->withErrors(['error' => 'There is a product that is being sent.']);
        }

        foreach ($salesOrder->details as $detail) {
            $detail->update([
                'status' => 'deleted', // Set status to deleted
            ]);
            $detail->delete(); // Soft delete the detail
        }
    
        // Set status to 'deleted' and then soft delete the sales order itself
        $salesOrder->update([
            'status' => 'deleted', // Set status to deleted
        ]);
        $salesOrder->delete();
    
        // Redirect back to the sales order index with a success message
        return redirect()->route('sales_order.index', $salesOrder->id)->with('success', 'Sales Order deleted successfully.');
    }
        
        public function getSalesOrdersByCustomer($customerId)
    {
        $salesOrders = SalesOrder::where('customer_id', $customerId)
                                ->where('status', 'pending') // Add the condition for 'pending' status
                                ->get();

        return response()->json(['salesOrders' => $salesOrders]);
    }

}
