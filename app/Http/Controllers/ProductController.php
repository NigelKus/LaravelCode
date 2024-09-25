<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Validation\Rule;
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $collection = $request->input('collection');
        
        // Define valid statuses
        $validStatuses = ['active', 'trashed'];
    
        // Build the query
        $query = Product::query();
        
        // Always exclude products with the status 'deleted'
        $query->where('status', '!=', Product::STATUS_DELETED);
        
        // Apply status filter
        if ($status !== null) {
            if (in_array($status, $validStatuses)) {
                $query->where('status', $status);
            }
        }
        
        // Apply collection filter  
        if ($collection) {
            $query->where('collection', $collection);
        }
        
        // Fetch products
        $products = $query->get();
        
        // Define possible statuses and collections
        $statuses = $validStatuses;
        $collections = [
            'Waris Classic Edition',
            'Waris Special Dragon Edition',
            'Waris Special Eid Edition',
        ];
        
        return view('layouts.master.product.index', compact('products', 'statuses', 'collections'));
    }
    
    
    

    public function create()
    {
        return view('layouts.master.product.create');
    }

    public function store(Request $request)
    {
        // Validate the request data
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
            // Check if the combination of collection and weight already exists
            $exists = Product::where('collection', $request->input('collection'))
                            ->where('weight', $request->input('weight'))
                            ->exists();
    
            if ($exists) {
                return redirect()->back()->withErrors([
                    'collection' => 'The combination of collection and weight already exists.'
                ])->withInput();
            }
    
            // Create the new product record
            $product = Product::create($request->all());
    
            // Redirect to the show page with a success message
            return redirect()->route('product.show', ['id' => $product->id])
                            ->with('success', 'Product created successfully.');
    
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create product. Please try again.')->withInput();
        }
    }
    


    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('layouts.master.product.show', compact('product'));
    }

    public function edit($id)
    {
        // Retrieve the product by ID
        $product = Product::findOrFail($id);

        // Pass the product to the edit view
        return view('layouts.master.product.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming data
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                // Ensure 'code' is unique in the 'mstr_product' table except for the current record
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
    
        // Find the product by ID or fail
        $product = Product::findOrFail($id);
    
        // Check if the combination of collection and weight is unique, excluding the current record
        $exists = Product::where('collection', $request->input('collection'))
                            ->where('weight', $request->input('weight'))
                            ->where('id', '!=', $id)
                            ->exists();
    
        if ($exists) {
            return redirect()->back()->withErrors([
                'collection' => 'The combination of collection and weight already exists.'
            ])->withInput();
        }
    
        // Update the product with validated data
        $product->update($request->all());
    
        // Redirect to the index page with success message
        return redirect()->route('product.index')->with('success', 'Product updated successfully.');
    }
    

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => Product::STATUS_DELETED]);

        return redirect()->route('product.index')->with('success', 'Product deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        // Find the product by ID
        $product = Product::findOrFail($id);

        // Update the status
        $product->status = $request->input('status');
        $product->save();

        // Redirect to the show page with a success message
        return redirect()->route('product.show', $id)->with('success', 'Product status updated successfully.');
    }
}
