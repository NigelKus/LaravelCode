<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller

{

    public function index(Request $request)
    {
        // Define the allowed statuses
        $allowedStatuses = ['active', 'trashed'];

        // Get the status from the request, if any
        $status = $request->input('status');
    
        // Build the query
        $suppliers = Supplier::whereIn('status', $allowedStatuses)
            ->when($status && in_array($status, $allowedStatuses), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();
    
        return view('layouts.master.supplier.index', compact('suppliers', 'allowedStatuses'));
    
    }

    public function create()
    {
        return view('layouts.master.supplier.create');
    }

        public function show($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('layouts.master.supplier.show', compact('supplier'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:mstr_supplier,name',
            'code' => 'required|string|max:255|unique:mstr_supplier,code',
            'supplier_category' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:mstr_supplier,phone',
            'description' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_city' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:mstr_supplier,email',
        ], [
            'name.unique' => 'The name has already been taken.',
            'code.unique' => 'The code has already been taken.',
            'phone.unique' => 'The phone number has already been taken.',
            'email.unique' => 'The email has already been taken.',
        ]);
    
        try {
            $supplier = Supplier::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'supplier_category' => $request->input('supplier_category'),
                'address' => $request->input('address'),
                'phone' => $request->input('phone'),
                'description' => $request->input('description'),
                'birth_date' => $request->input('birth_date'),
                'birth_city' => $request->input('birth_city'),
                'email' => $request->input('email'),
            ]);
    
            return redirect()->route('supplier.show', ['id' => $supplier->id])
                                ->with('success', value: 'Supplier created successfully.');
    
        } catch (\Exception $e) {
    
            return redirect()->back()->with('error', 'Failed to create supplier. Please try again.');
        }
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('layouts.master.supplier.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_supplier', 'code')->ignore($id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_supplier', 'name')->ignore($id),
            ],
            'supplier_category' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mstr_supplier', 'phone')->ignore($id),
            ],
            'description' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_city' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('mstr_supplier', 'email')->ignore($id),
            ],
        ]);
    
        $supplier = Supplier::findOrFail($id);

        $supplier->update($validatedData);
    
        return redirect()->route('supplier.show', $supplier->id)->with('success', 'Supplier updated successfully.');
    }
    
    

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
    
        // Update the status to 'deleted' and set the deleted_at timestamp
        $supplier->update([
            'status' => Supplier::STATUS_DELETED,
            'deleted_at' => Carbon::now() // Set the current timestamp for deleted_at
        ]);
    
        return redirect()->route('supplier.index')->with('success', 'Supplier status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $supplier = Supplier::findOrFail($id);

        $supplier->status = $request->input('status');
        $supplier->save();
        
        return redirect()->route('supplier.show', $id)->with('success', 'Supplier status updated successfully.');
    }
}