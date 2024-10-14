<?php

namespace App\Http\Controllers;

use random;
use Illuminate\Support\Str;
use App\Models\Journal;
use App\Models\Posting;
use App\Models\PaymentOrder;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use App\Models\PaymentPurchase;
use App\Models\PurchaseInvoice;
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
            ->orderBy('code', 'asc')
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
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
        $CoA = ChartOfAccount::with(['postings.journal' => function ($query) {
            $query->orderBy('date', 'asc');
        }])->findOrFail($id);
    
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

        $code = $CoA->code . '-del-' . Str::uuid(); 
        
        $CoA->update([
            'code' => $code,
            'status' => ChartOfAccount::STATUS_DELETED, 
        ]);

        $CoA->delete();

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

    public function routeTransaction($postingId)
    {
        $posting = Posting::findOrFail($postingId);

        $journal = Journal::where('id', $posting->journal_id)->firstOrFail();
    
        $firstTwoLetters = substr($journal->description, 0, 2);
        
        switch ($firstTwoLetters) {
            case 'SI':
                $salesInvoice = SalesInvoice::where('code', $journal->description)->firstOrFail();
                return redirect()->route('sales_invoice.show', $salesInvoice->id);
            case 'PS':
                $paymentOrder = PaymentOrder::where('code', $journal->description)->firstOrFail();
                return redirect()->route('payment_order.show', $paymentOrder->id);
            case 'PI':
                $purchaseInvoice = PurchaseInvoice::where('code', $journal->description)->firstOrFail();
                return redirect()->route('purchase_invoice.show', $purchaseInvoice->id);
            case 'PP':
                $paymentPurchase = PaymentPurchase::where('code', $journal->description)->firstOrFail();
                return redirect()->route('payment_purchase.show', $paymentPurchase->id);
            default:
                return 'Unknown Transaction';
        }
    }
    
}