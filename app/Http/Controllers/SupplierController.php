<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $allowedStatuses = ['active', 'trashed'];
        $status = $request->input('status');
        $suppliers = Supplier::whereIn('status', $allowedStatuses)
            ->when($status && in_array($status, $allowedStatuses), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();
    
        return view('layouts.master.supplier.index', compact('suppliers', 'allowedStatuses'));
    
    }

    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        return view('layouts.master.supplier.create');
    }

        public function show(Request $request,$id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $supplier = Supplier::findOrFail($id);

        return view('layouts.master.supplier.show', compact('supplier'));
    }
    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

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

    public function edit(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $supplier = Supplier::findOrFail($id);

        return view('layouts.master.supplier.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

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
    
    

    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            'status' => Supplier::STATUS_DELETED
        ]);

        $supplier->delete();
    
        return redirect()->route('supplier.index')->with('success', 'Supplier status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['Admin'])) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $supplier = Supplier::findOrFail($id);

        $supplier->status = $request->input('status');
        $supplier->save();
        
        return redirect()->route('supplier.show', $id)->with('success', 'Supplier status updated successfully.');
    }
}