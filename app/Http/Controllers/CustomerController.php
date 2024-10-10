<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;


class CustomerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Define the allowed statuses
        $allowedStatuses = ['active', 'trashed'];
    
        // Get the status from the request, if any
        $status = $request->input('status');
    
        // Build the query
        $customers = Customer::whereIn('status', $allowedStatuses)
            ->when($status && in_array($status, $allowedStatuses), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();
    
        // Pass the customers and statuses to the view
        return view('layouts.master.customer.index', compact('customers', 'allowedStatuses'));
    }
    

        public function create()
    {
        return view('layouts.master.customer.create');
    }

        public function show($id)
    {
        $customer = Customer::findOrFail($id);

        return view('layouts.master.customer.show', compact('customer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:mstr_customer,name',
            'code' => 'required|string|max:255|unique:mstr_customer,code',
            'sales_category' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:mstr_customer,phone',
            'description' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_city' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:mstr_customer,email',
        ], [
            'name.unique' => 'The name has already been taken.',
            'code.unique' => 'The code has already been taken.',
            'phone.unique' => 'The phone number has already been taken.',
            'email.unique' => 'The email has already been taken.',
        ]);
    
        try {
            $customer = Customer::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'sales_category' => $request->input('sales_category'),
                'address' => $request->input('address'),
                'phone' => $request->input('phone'),
                'description' => $request->input('description'),
                'birth_date' => $request->input('birth_date'),
                'birth_city' => $request->input('birth_city'),
                'email' => $request->input('email'),
            ]);
    
            return redirect()->route('customer.show', ['id' => $customer->id])
                                ->with('success', 'Customer created successfully.');
    
        } catch (\Exception $e) {
    
            return redirect()->back()->with('error', 'Failed to create customer. Please try again.');
        }
    }
    
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);

        return view('layouts.master.customer.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_customer', 'code')->ignore($id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mstr_customer', 'name')->ignore($id),
            ],
            'sales_category' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mstr_customer', 'phone')->ignore($id),
            ],
            'description' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_city' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('mstr_customer', 'email')->ignore($id),
            ],
        ]);
    
        $customer = Customer::findOrFail($id);

        $customer->update($validatedData);
    
        return redirect()->route('customer.show', $customer->id)->with('success', 'Customer updated successfully.');
    }
    
    

    public function destroy($id)
    {
        // Find the customer or fail if not found
        $customer = Customer::findOrFail($id);
    
        // Update the status to 'deleted' and set the deleted_at timestamp
        $customer->update([
            'status' => Customer::STATUS_DELETED,
            'deleted_at' => Carbon::now() // Set the current timestamp for deleted_at
        ]);
    
        // Redirect back to the customer index with a success message
        return redirect()->route('customer.index')->with('success', 'Customer status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $customer = Customer::findOrFail($id);

        $customer->status = $request->input('status');
        $customer->save();
        
        return redirect()->route('customer.show', $id)->with('success', 'Customer status updated successfully.');
    }

    public function restore($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore(); // Restores the soft deleted record
        return redirect()->route('customer.index')->with('success', 'Customer restored successfully');
    }

    public function forceDelete($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->forceDelete(); // Permanently deletes the record
        return redirect()->route('customer.index')->with('success', 'Customer permanently deleted');
    }
}
