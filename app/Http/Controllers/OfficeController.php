<?php

namespace App\Http\Controllers;

use random;
use App\Models\Office;
use App\Models\Posting;
use Illuminate\Support\Str;
use App\Models\PaymentOrder;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JournalVoucher;
use Illuminate\Support\Carbon;
use App\Models\PaymentPurchase;
use App\Models\PurchaseInvoice;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{

    public function index(Request $request){
        $allowedStatuses = ['active', 'trashed'];
    
        $status = $request->input('status');
    
        $offices = Office::whereIn('status', $allowedStatuses)
            ->when($status && in_array($status, $allowedStatuses), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();
        return view('layouts.master.office.index', compact('offices', 'allowedStatuses'));
    }

    public function create(){
        return view('layouts.master.office.create');
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:mstr_office,name',
            'code' => 'required|string|max:255|unique:mstr_office,code',
            'location' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:mstr_office,phone',
            'opening_date' => 'nullable|date',
        ], [
            'name.unique' => 'The name has already been taken.',
            'code.unique' => 'The code has already been taken.',
            'phone.unique' => 'The phone number has already been taken.',
        ]);
    
        try {
            $office = Office::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'location' => $request->input('location'),
                'phone' => $request->input('phone'),
                'opening_date' => $request->input('opening_date'),
            ]);
    
            return redirect()->route('office.show', ['id' => $office->id])
                                ->with('success', 'Office created successfully.');
    
        } catch (\Exception $e) {
    
            return redirect()->back()->with('error', 'Failed to create office. Please try again.');
        }
    }

    public function show($id)
    {
        $office = Office::findOrFail($id);

        return view('layouts.master.office.show', compact('office'));
    }

    public function update(Request $request, $id)
    {
        $a = $request->validate([
            'name' => 'required|string|max:255|unique:mstr_office,name',
            'code' => 'required|string|max:255|unique:mstr_office,code',
            'location' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:mstr_office,phone',
            'opening_date' => 'nullable|date',
        ], [
            'name.unique' => 'The name has already been taken.',
            'code.unique' => 'The code has already been taken.',
            'phone.unique' => 'The phone number has already been taken.',
        ]);
    
        $office = Office::findOrFail($id);

        $office->update($a);
    
        return redirect()->route('office.show', $office->id)->with('success', 'Office updated successfully.');
    }
    

    public function destroy($id)
    {
        $office = Office::findOrFail($id);
    
        $office->update([
            'status' => Office::STATUS_DELETED,
        ]);
        $office->save();
        $office->delete();

        return redirect()->route('office.index')->with('success', 'Office status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $office = Office::findOrFail($id);

        $office->status = $request->input('status');
        $office->save();
        
        return redirect()->route('office.show', $id)->with('success', 'Office status updated successfully.');
    }

    public function edit($id)
    {
        $office = Office::findOrFail($id);

        return view('layouts.master.office.edit', compact('office'));
    }
}

