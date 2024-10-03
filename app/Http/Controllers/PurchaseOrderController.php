<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrderDetail;
use Database\Factories\CodeFactory;

class PurchaseOrderController extends Controller

{
    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; // Define your statuses
        $query = PurchaseOrder::query();

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
        $purchaseOrders = $query->paginate($perPage);
    
        return view('layouts.transactional.purchase_order.index', compact('purchaseOrders', 'statuses'));
    }

    public function create()
    {
        // Fetch only active customers
        $suppliers = Supplier::where('status', 'active')->get();
    
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Return the view with the customers and products data
        return view('layouts.transactional.purchase_order.create', compact('suppliers', 'products'));
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
            'supplier_id' => 'required|integer|exists:mstr_supplier,id',
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
        $purchaseOrderCode = CodeFactory::generatePurchaseOrdersCode();
    
        // Begin a database transaction for both SalesOrder and SalesOrderDetail
        DB::beginTransaction();
        // Create a new sales order record
        $purchaseOrder = PurchaseOrder::create([  
            'code' => $purchaseOrderCode,
            'supplier_id' => $validatedData['supplier_id'],
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
                'purchaseorder_id' => $purchaseOrder->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'pending',
            ];
        }
        
        // DD($detailsData);
        // Insert products into the salesorder_detail table in a batch
        PurchaseOrderDetail::insert($detailsData);
    
        // Commit the transaction
        DB::commit();
    
        // Redirect with success message
        return redirect()->route('purchase_order.show', ['id' => $purchaseOrder->id])
            ->with('success', 'Purchase Order created successfully.')
            ->with('purchase_order_code', $purchaseOrderCode);
    }

    public function show($id)
    {
        // Fetch the sales order and its details
        $purchaseOrder = PurchaseOrder::with('supplier', 'details.product')->findOrFail($id);

        
        // Calculate total price
        $totalPrice = $purchaseOrder->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        
        // Return the view with the sales order and its details
        return view('layouts.transactional.purchase_order.show', compact('purchaseOrder', 'totalPrice'));
    }

    public function destroy($id)
    {
        // Find the sales order or fail if not found
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        // Initialize a flag to track if any product fails the check
        $hasInsufficientQuantity = false;
    
        // Check each product in the sales order details
        foreach ($purchaseOrder->details as $detail) {
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

        foreach ($purchaseOrder->details as $detail) {
            $detail->update([
                'status' => 'deleted', // Set status to deleted
            ]);
            $detail->delete(); // Soft delete the detail
        }
    
        // Set status to 'deleted' and then soft delete the sales order itself
        $purchaseOrder->update([
            'status' => 'deleted', // Set status to deleted
        ]);
        $purchaseOrder->delete();
    
        // Redirect back to the sales order index with a success message
        return redirect()->route('purchase_order.index', $purchaseOrder->id)->with('success', 'Purchase Order deleted successfully.');
    }

    public function edit($id)
    {
        // Fetch the sales order with details
        $purchaseOrder = PurchaseOrder::with('details.product')->findOrFail($id);

        // Convert date to Carbon object if it's not already
        $purchaseOrder->date = \Carbon\Carbon::parse($purchaseOrder->date);

        // Fetch customers and products
        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('layouts.transactional.purchase_order.edit', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(Request $request, $id)
    {

        $purchaseOrder = PurchaseOrder::findOrFail($id);

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
        $purchaseOrderDetails = [];
        foreach ($filteredData as $data) {
            list($product_id, $qty, $price_each, $price_total) = $data;
    
            $purchaseOrderDetails[] = [
                'product_id' => $product_id,
                'quantity' => !is_null($qty) ? (int)$qty : null,
                'price' => !is_null($price_each) ? (float)str_replace(',', '', $price_each) : null,
                'price_total' => !is_null($price_total) ? (float)str_replace(',', '', $price_total) : null,
            ];
        }
        
        // Update the sales order
        $purchaseOrder->update([
            'supplier_id' => $request->input('supplier_id'),
            'description' => $request->input('description'),
            'date' => \Carbon\Carbon::parse($request->input('date')),
        ]);
        
        // Get the arrays from the request
        $product_ids = $request->input('product_ids', []);
        
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
    
        // Initialize an empty array to hold the combined details
        $purchaseOrderDetails = [];
    
        // Determine the number of items in each array
        $length = count($product_ids);
    
        for ($i = 0; $i < $length; $i++) {
            // Only add to the details array if the product_id is not null
            if (!is_null($product_ids[$i])) {
                $purchaseOrderDetails[] = [
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
        foreach ($purchaseOrderDetails as $detail) {
            PurchaseOrderDetail::updateOrCreate(
                [
                    'purchaseorder_id' => $purchaseOrder->id,
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
        PurchaseOrderDetail::where('purchaseorder_id', $purchaseOrder->id)
            ->whereNotIn('product_id', array_column($purchaseOrderDetails, 'product_id'))
            ->delete();

        // Redirect to the sales orders index page with a success message
        return redirect()->route('purchase_order.show', $purchaseOrder->id)->with('success', 'Purchase Order updated successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->status = $request->input('status');
        $purchaseOrder->save();

        return redirect()->route('purchase_order.show', $purchaseOrder->id)->with('success', 'Status updated successfully.');
        
    }

    public function getPurchaseOrdersBySupplier($supplierId)
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplierId)
                                ->where('status', 'pending') // Add the condition for 'pending' status
                                ->get();

        return response()->json(['purchaseOrders' => $purchaseOrders]);
    }

    public function getProducts($purchaseOrderId)
    {
        try {
            // Find the purchase order and eager load the details with their associated product
            $purchaseOrder = PurchaseOrder::with(['details' => function ($query) {
                // Filter details to only include those with status "pending"
                $query->where('status', 'pending');
            }, 'details.product'])->find($purchaseOrderId);
            
            
            if (!$purchaseOrder) {
                throw new \Exception('purchase order not found');
            }
            
            // Map the details to the required format
                $productsData = $purchaseOrder->details->map(function ($detail) {
                // Use the accessor to get the remaining quantity
                $remainingQuantity = $detail->quantity_remaining ?? 0; // Default value if null
                
                return [
                    'product_id' => $detail->id, //product id
                    'code' => $detail->product->code,
                    'quantity' => $detail->quantity, // Total quantity requested
                    'price' => $detail->price,
                    'requested' => $detail->quantity, // Quantity requested
                    'remaining_quantity' => $remainingQuantity,
                    'purchase_order_detail_id' => $detail->purchase_order_detail_id // Corrected key name
                ];
            });
            
            return response()->json(['products' => $productsData]);
        
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            
            // If you want to return an error response instead of logging it
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }
}