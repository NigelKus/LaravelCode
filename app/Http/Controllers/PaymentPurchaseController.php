<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PaymentPurchase;
use Illuminate\Validation\Rule;

class PaymentPurchaseController extends Controller

{

    public function index(Request $request)
    {
        $statuses = ['pending', 'completed']; // Define your statuses
        $query = PaymentPurchase::query();

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
        $paymentPurchases = $query->paginate($perPage);

        return view('layouts.transactional.payment_purchase.index', compact('paymentPurchases', 'statuses')); // Adjust the view as necessary
    }

    public function create()
    {
        // Fetch all active customers
        $suppliers = Supplier::where('status', 'active')->get();
    
        // Return the view with only the customers
        return view('layouts.transactional.payment_purchase.create', compact('suppliers'));
    }
}