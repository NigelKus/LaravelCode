<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Validation\Rule;
class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $status = $request->input('status');
        $collection = $request->input('collection');
        $validStatuses = ['active', 'trashed'];
    
        $query = Product::query();
        
        $query->where('status', '!=', Product::STATUS_DELETED);
        
        if ($status !== null) {
            if (in_array($status, $validStatuses)) {
                $query->where('status', $status);
            }
        }
        
        if ($collection) {
            $query->where('collection', $collection);
        }
        
        $products = $query->get();
        
        $statuses = $validStatuses;
        $collections = [
            'Waris Classic Edition',
            'Waris Special Dragon Edition',
            'Waris Special Eid Edition',
        ];
        
        return view('layouts.master.product.index', compact('products', 'statuses', 'collections'));
    }

    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        return view('layouts.master.product.create');
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'code' => 'required|string|max:255|unique:mstr_product,code',
            'collection' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'The code has already been taken.',
            'collection.unique' => 'The combination of code, collection, and weight already exists.',
            'weight.numeric' => 'Weight must be a number.',
            'price.numeric' => 'Price must be a number.',
            'stock.integer' => 'Stock must be an integer.',
        ]);
    
        try {
            $exists = Product::where('collection', $request->input('collection'))
                            ->where('weight', $request->input('weight'))
                            ->exists();
    
            if ($exists) {
                return redirect()->back()->withErrors([
                    'collection' => 'The combination of collection and weight already exists.'
                ])->withInput();
            }
    
            $product = Product::create($request->all());
    
            return redirect()->route('product.show', ['id' => $product->id])
                            ->with('success', 'Product created successfully.');
    
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create product. Please try again.')->withInput();
        }
    }
    
    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $product = Product::findOrFail($id);
        return view('layouts.master.product.show', compact('product'));
    }

    public function edit(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $product = Product::findOrFail($id);

        return view('layouts.master.product.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_product', 'code')->ignore($id)
            ],
            'collection' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'The code has already been taken.',
            'collection.unique' => 'The combination of collection and weight already exists.',
            'weight.numeric' => 'Weight must be a number.',
            'price.numeric' => 'Price must be a number.',
            'stock.integer' => 'Stock must be an integer.',
        ]);
        $product = Product::findOrFail($id);
    
        $exists = Product::where('collection', $request->input('collection'))
                            ->where('weight', $request->input('weight'))
                            ->where('id', '!=', $id)
                            ->exists();
    
        if ($exists) {
            return redirect()->back()->withErrors([
                'collection' => 'The combination of collection and weight already exists.'
            ])->withInput();
        }
    
        $product->update($request->all());
    
        return redirect()->route('product.index')->with('success', 'Product updated successfully.');
    }
    

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $product = Product::findOrFail($id);
        $product->update(['status' => Product::STATUS_DELETED]);
        $product->save();
        $product->delete();
        
        return redirect()->route('product.index')->with('success', 'Product deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $product = Product::findOrFail($id);

        $product->status = $request->input('status');
        $product->save();

        return redirect()->route('product.show', $id)->with('success', 'Product status updated successfully.');
    }
}
