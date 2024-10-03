<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CoAController extends Controller

{

    public function index(Request $request)
    {
        // Define the allowed statuses
        $allowedStatuses = ['active', 'trashed'];

        // Get the status from the request, if any
        $status = $request->input('status');
    
        // Build the query
        $CoAs = ChartOfAccount::whereIn('status', $allowedStatuses)
            ->when($status && in_array($status, $allowedStatuses), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();
    
        return view('layouts.master.CoA.index', compact('CoAs', 'allowedStatuses'));
    
    }

    public function create()
    {
        return view('layouts.master.CoA.create');
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string|max:255|unique:mstr_coa,name',
            'code' => 'required|string|max:255|unique:mstr_coa,code',
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'The name has already been taken.',
            'code.unique' => 'The code has already been taken.',
        ]);
    
        try {
            $CoA = ChartOfAccount::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'description' => $request->input('description'),
                'status' => 'active'
            ]);
    
            return redirect()->route('CoA.show', ['id' => $CoA->id])
                                ->with('success', value: 'CoA created successfully.');
    
        } catch (\Exception $e) {
    
            return redirect()->back()->with('error', 'Failed to create chart of account. Please try again.');
        }
    }

    public function show($id)
    {
        $CoA = ChartOfAccount::with(['postings.journal'])->findOrFail($id);
        return view('layouts.master.CoA.show', compact('CoA'));
    }
    
    
    public function edit($id)
    {
        $CoA = ChartOfAccount::findOrFail($id);

        

        return view('layouts.master.CoA.edit', compact('CoA'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_coa', 'code')->ignore($id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_coa', 'name')->ignore($id),
            ],
            'description' => 'nullable|string',
        ]);
    
        $CoA = ChartOfAccount::findOrFail($id);

        $CoA->update($validatedData);
    
        return redirect()->route('CoA.show', $CoA->id)->with('success', 'Chart of Account updated successfully.');
    }

    public function destroy($id)
    {
        $CoA = ChartOfAccount::findOrFail($id);
    
        // Update the status to 'deleted' and set the deleted_at timestamp
        $CoA->update([
            'status' => ChartOfAccount::STATUS_DELETED,
            'deleted_at' => Carbon::now() // Set the current timestamp for deleted_at
        ]);
    
        return redirect()->route('CoA.index')->with('success', 'Chart of Account status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $CoA = ChartOfAccount::findOrFail($id);

        $CoA->status = $request->input('status');
        $CoA->save();
        
        return redirect()->route('CoA.show', $id)->with('success', 'Chart of Account status updated successfully.');
    }
}