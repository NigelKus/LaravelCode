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
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');}
        $statuses = ['pending', 'completed']; 
        $query = SalesOrder::with(['customer' => function ($q) {
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
        $salesOrders = $query->paginate($perPage);
        return view('layouts.transactional.sales_order.index', compact('salesOrders', 'statuses'));
    }
    
    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        return view('layouts.transactional.sales_order.create-copy', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $productIds = $request->input('product_ids', []);
        $quantities = $request->input('qtys', []);
        $prices = $request->input('price_eachs', []);
        $priceTotals = $request->input('price_totals', []);
        $filteredData = array_filter(array_map(null, $productIds, $quantities, $prices, $priceTotals), function($item) {
            return $item[0] !== null; 
        });
        $filteredProductIds = array_column($filteredData, 0);
        $filteredQuantities = array_column($filteredData, 1);
        $filteredPrices = array_column($filteredData, 2);
        $filteredPriceTotals = array_column($filteredData, 3);
        if (empty($filteredProductIds)) {
            return redirect()->back()->withErrors(['error' => 'At least one product must be selected.']);
        }
        $filteredPrices = array_map(fn($value) => str_replace(',', '', $value), $filteredPrices);
        $filteredPriceTotals = array_map(fn($value) => str_replace(',', '', $value), $filteredPriceTotals);
        $request->merge([
            'product_ids' => array_values($filteredProductIds),
            'qtys' => array_values($filteredQuantities),
            'price_eachs' => array_values($filteredPrices),
            'price_totals' => array_values($filteredPriceTotals),
        ]);
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
        $salesOrderCode = CodeFactory::generateSalesOrderCode();
        DB::beginTransaction();
        $salesOrder = SalesOrder::create([  
            'code' => $salesOrderCode,
            'customer_id' => $validatedData['customer_id'],
            'description' => $validatedData['description'],
            'status' => 'pending',
            'date' => $validatedData['date'],
        ]);
        $productIds = $validatedData['product_ids'];
        $quantities = $validatedData['qtys'];
        $prices = $validatedData['price_eachs'];
        if (count($productIds) !== count($quantities) || count($productIds) !== count($prices)) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Mismatch between product details.']);
        }
        $detailsData = [];
        foreach ($productIds as $index => $productId) {
            $quantity = $quantities[$index];
            $price = $prices[$index];
            $detailsData[] = [
                'salesorder_id' => $salesOrder->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'pending',
            ];
        }
        SalesorderDetail::insert($detailsData);
        DB::commit();
        return redirect()->route('sales_order.show', ['id' => $salesOrder->id])
            ->with('success', 'Sales Order created successfully.')
            ->with('sales_order_code', $salesOrderCode);
    }
    
    
    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $salesOrder = SalesOrder::with([
            'customer' => function ($query) {
                $query->withTrashed();
            },
            'details.product' => function ($query) {
                $query->withTrashed();
            }
        ])->findOrFail($id);


        $deleted = ($salesOrder->customer->status == 'deleted');
        
        $totalPrice = $salesOrder->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        return view('layouts.transactional.sales_order.show', compact('salesOrder', 'totalPrice', 'deleted'));
    }

        public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);
        $salesOrder = SalesOrder::findOrFail($id);
        $salesOrder->status = $request->input('status');
        $salesOrder->save();
        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Status updated successfully.');
        
    }
    
        public function edit(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $salesOrder = SalesOrder::with('details.product')->findOrFail($id);
        $salesOrder->date = \Carbon\Carbon::parse($salesOrder->date);
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('layouts.transactional.sales_order.edit', compact('salesOrder', 'customers', 'products'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $salesOrder = SalesOrder::findOrFail($id);
        $product_ids = $request->input('product_ids', []);
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
        $filteredData = array_filter(array_map(null, $product_ids, $qtys, $price_eachs, $price_totals), function($item) {
            return !is_null($item[0]); 
        });
        if (empty($filteredData)) {
            return redirect()->back()->withErrors(['product_ids' => 'There is a missing product.'])->withInput();
        }
        $salesOrderDetails = [];
        foreach ($filteredData as $data) {
            list($product_id, $qty, $price_each, $price_total) = $data;
    
            $salesOrderDetails[] = [
                'product_id' => $product_id,
                'quantity' => !is_null($qty) ? (int)$qty : null,
                'price' => !is_null($price_each) ? (float)str_replace(',', '', $price_each) : null,
                'price_total' => !is_null($price_total) ? (float)str_replace(',', '', $price_total) : null,
            ];}
        $salesOrder->update([
            'customer_id' => $request->input('customer_id'),
            'description' => $request->input('description'),
            'date' => \Carbon\Carbon::parse($request->input('date')),
        ]);
        $product_ids = $request->input('product_ids', []);
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
        $salesOrderDetails = [];
        $length = count($product_ids);
        for ($i = 0; $i < $length; $i++) {
            if (!is_null($product_ids[$i])) {
                $salesOrderDetails[] = [
                    'product_id' => $product_ids[$i],
                    'quantity' => !is_null($qtys[$i]) ? (int)$qtys[$i] : null,
                    'price' => !is_null($price_eachs[$i]) ? (float)str_replace(',', '', $price_eachs[$i]) : null,
                    'price_total' => !is_null($price_totals[$i]) ? (float)str_replace(',', '', $price_totals[$i]) : null,
                ];
            }
        }
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
        SalesOrderDetail::where('salesorder_id', $salesOrder->id)
            ->whereNotIn('product_id', array_column($salesOrderDetails, 'product_id'))
            ->delete();
        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Sales Order updated successfully.');
    }

    public function getProducts($salesOrderId)
    {
        try {
            $salesOrder = SalesOrder::with(['details' => function ($query) {
                $query->where('status', 'pending');
            }, 'details.product'])->find($salesOrderId);
            
            
            if (!$salesOrder) {
                throw new \Exception('Sales order not found');
            }
            
                $productsData = $salesOrder->details->map(function ($detail) {
                $remainingQuantity = $detail->quantity_remaining ?? 0; 
                
                return [
                    'product_id' => $detail->id,
                    'code' => $detail->product->code,
                    'quantity' => $detail->quantity, 
                    'price' => $detail->price,
                    'requested' => $detail->quantity, 
                    'remaining_quantity' => $remainingQuantity,
                    'sales_order_detail_id' => $detail->sales_order_detail_id 
                ];
            });
            
            return response()->json(['products' => $productsData]);
        
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $salesOrder = SalesOrder::findOrFail($id);

        $hasInsufficientQuantity = false;
    
        foreach ($salesOrder->details as $detail) {
            if ($detail->quantity_remaining < $detail->quantity) {
                $hasInsufficientQuantity = true;
                break; 
            }
        }
    
        if ($hasInsufficientQuantity) {
            return redirect()->back()->withErrors(['error' => 'There is a product that is being sent.']);
        }

        foreach ($salesOrder->details as $detail) {
            $detail->update([
                'status' => 'deleted', 
            ]);
            $detail->delete(); 
        }
    
        $salesOrder->update([
            'status' => 'deleted', 
        ]);
        $salesOrder->delete();
    
        return redirect()->route('sales_order.index', $salesOrder->id)->with('success', 'Sales Order deleted successfully.');
    }
        
        public function getSalesOrdersByCustomer($customerId)
    {
        $salesOrders = SalesOrder::where('customer_id', $customerId)
                                ->where('status', 'pending') 
                                ->get();

        return response()->json(['salesOrders' => $salesOrders]);
    }

}
