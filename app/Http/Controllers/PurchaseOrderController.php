<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrderDetail;
use Database\Factories\CodeFactory;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends Controller

{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }
        $statuses = ['pending', 'completed']; 
        $query = PurchaseOrder::with(['supplier' => function ($q) {
            $q->withTrashed(); }])
        ->whereNotIn('status', ['deleted', 'canceled', 'cancelled']);
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);}
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');}
        if ($request->has('supplier') && $request->supplier != '') {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%');
            });
        }
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('date', $request->date);}
        if ($request->has('sort')) {
            if ($request->sort == 'recent') {
                $query->orderBy('date', 'desc'); 
            } elseif ($request->sort == 'oldest') {
                $query->orderBy('date', 'asc'); 
            }
        }
        $perPage = $request->get('perPage', 10); 
        $purchaseOrders = $query->paginate($perPage);
        return view('layouts.transactional.purchase_order.index', compact('purchaseOrders', 'statuses'));
    }

    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $suppliers = Supplier::where('status', 'active')->get();
    
        $products = Product::where('status', 'active')->get();
    
        return view('layouts.transactional.purchase_order.create', compact('suppliers', 'products'));
    }
    
    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');}
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
            return redirect()->back()->withErrors(['error' => 'At least one product must be selected.']);}
        $filteredPrices = array_map(fn($value) => str_replace(',', '', $value), $filteredPrices);
        $filteredPriceTotals = array_map(fn($value) => str_replace(',', '', $value), $filteredPriceTotals);
        $request->merge([
            'product_ids' => array_values($filteredProductIds),
            'qtys' => array_values($filteredQuantities),
            'price_eachs' => array_values($filteredPrices),
            'price_totals' => array_values($filteredPriceTotals),
        ]);
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
        $purchaseOrderCode = CodeFactory::generatePurchaseOrdersCode();
        DB::beginTransaction();
        $purchaseOrder = PurchaseOrder::create([  
            'code' => $purchaseOrderCode,
            'supplier_id' => $validatedData['supplier_id'],
            'description' => $validatedData['description'],
            'status' => 'pending',
            'date' => $validatedData['date'],
        ]);
        $productIds = $validatedData['product_ids'];
        $quantities = $validatedData['qtys'];
        $prices = $validatedData['price_eachs'];
        if (count($productIds) !== count($quantities) || count($productIds) !== count($prices)) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Mismatch between product details.']);}
        $detailsData = [];
        foreach ($productIds as $index => $productId) {
            $quantity = $quantities[$index];
            $price = $prices[$index];
            $detailsData[] = [
                'purchaseorder_id' => $purchaseOrder->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'pending',
            ];
        }
        PurchaseOrderDetail::insert($detailsData);
        DB::commit();
        return redirect()->route('purchase_order.show', ['id' => $purchaseOrder->id])
            ->with('success', 'Purchase Order created successfully.')
            ->with('purchase_order_code', $purchaseOrderCode);
    }

    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');}
        $purchaseOrder = PurchaseOrder::with(['supplier' => function ($query) {
            $query->withTrashed();
        }, 'details.product'])
        ->findOrFail($id);
        $deleted = ($purchaseOrder->supplier->status == 'deleted');
        $totalPrice = $purchaseOrder->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        return view('layouts.transactional.purchase_order.show', compact('purchaseOrder', 'totalPrice', 'deleted'));
    }

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $hasInsufficientQuantity = false;
    
        foreach ($purchaseOrder->details as $detail) {
            
            if ($detail->quantity_remaining < $detail->quantity) {
                $hasInsufficientQuantity = true;
                break; 
            }
        }
    
        if ($hasInsufficientQuantity) {
            return redirect()->back()->withErrors(['error' => 'There is a product that is being sent.']);
        }

        foreach ($purchaseOrder->details as $detail) {
            $detail->update([
                'status' => 'deleted', 
            ]);
            $detail->delete(); 
        }
    
        $purchaseOrder->update([
            'status' => 'deleted', 
        ]);
        $purchaseOrder->delete();
    
        return redirect()->route('purchase_order.index', $purchaseOrder->id)->with('success', 'Purchase Order deleted successfully.');
    }

    public function edit(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $purchaseOrder = PurchaseOrder::with('details.product')->findOrFail($id);

        $purchaseOrder->date = \Carbon\Carbon::parse($purchaseOrder->date);

        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('layouts.transactional.purchase_order.edit', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

        $purchaseOrder = PurchaseOrder::findOrFail($id);

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
        
        $purchaseOrder->update([
            'supplier_id' => $request->input('supplier_id'),
            'description' => $request->input('description'),
            'date' => \Carbon\Carbon::parse($request->input('date')),
        ]);
        
        $product_ids = $request->input('product_ids', []);
        
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
    
        $purchaseOrderDetails = [];
    
        $length = count($product_ids);
    
        for ($i = 0; $i < $length; $i++) {
            if (!is_null($product_ids[$i])) {
                $purchaseOrderDetails[] = [
                    'product_id' => $product_ids[$i],
                    'quantity' => !is_null($qtys[$i]) ? (int)$qtys[$i] : null,
                    'price' => !is_null($price_eachs[$i]) ? (float)str_replace(',', '', $price_eachs[$i]) : null,
                    'price_total' => !is_null($price_totals[$i]) ? (float)str_replace(',', '', $price_totals[$i]) : null,
                ];
            }
        }
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
        PurchaseOrderDetail::where('purchaseorder_id', $purchaseOrder->id)
            ->whereNotIn('product_id', array_column($purchaseOrderDetails, 'product_id'))
            ->delete();

        return redirect()->route('purchase_order.show', $purchaseOrder->id)->with('success', 'Purchase Order updated successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin', 'Finance 1'])) {
            abort(403, 'Unauthorized access');
        }

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
                                ->where('status', 'pending') 
                                ->get();

        return response()->json(['purchaseOrders' => $purchaseOrders]);
    }

    public function getProducts($purchaseOrderId)
    {
        try {
            $purchaseOrder = PurchaseOrder::with(['details' => function ($query) {
                $query->where('status', 'pending');
            }, 'details.product'])->find($purchaseOrderId);
            
            
            if (!$purchaseOrder) {
                throw new \Exception('purchase order not found');
            }
            
                $productsData = $purchaseOrder->details->map(function ($detail) {
                $remainingQuantity = $detail->quantity_remaining ?? 0; 
                
                return [
                    'product_id' => $detail->id,
                    'code' => $detail->product->code,
                    'quantity' => $detail->quantity, 
                    'price' => $detail->price,
                    'requested' => $detail->quantity, 
                    'remaining_quantity' => $remainingQuantity,
                    'purchase_order_detail_id' => $detail->purchase_order_detail_id 
                ];
            });
            
            return response()->json(['products' => $productsData]);
        
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }
}