<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::all();
        return view('layouts.master.user.index', ['users' => $users]);
    }

    public function create()
    {
        $offices = Office::where('status', 'active')->get();
        return view('layouts.master.user.create', compact('offices'));
    }

    public function store(Request $request)
    {   
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'office_id' => 'nullable|exists:mstr_office,id', 
            'role' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email', 
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date', 
            'birth_location' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:users,phone', 
            'password' => 'required|string|min:8|confirmed', 
        ], [
            'name.unique' => 'The name has already been taken.',
            'office_id.exists' => 'The selected office is invalid.',
            'email.unique' => 'The email has already been taken.',
            'phone.unique' => 'The phone number has already been taken.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);
        
        User::create([
            'name' => $request->name,
            'office_id' => $request->office_id,
            'role' => $request->role,
            'email' => $request->email,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'birth_location' => $request->birth_location,
            'phone' => $request->phone,
            'password' => Hash::make($request->password), 
        ]);
    
        return redirect()->route('user.index')->with('success', 'User created successfully.');
    }

        public function show($id)
    {
        $user = User::with('office')->findOrFail($id);

        $office = $user->office; 

        return view('layouts.master.user.show', compact('office', 'user'));
    }


    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|exists:users,name',
            'office_id' => 'nullable|exists:mstr_office,id', 
            'role' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|exists:users,email', 
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date', 
            'birth_location' => 'nullable|string',
            'phone' => 'nullable|string|max:20', 
            'password' => 'nullable|string|min:8|confirmed', 
        ], [
            'name.exists' => 'The name has already been taken.',
            'office_id.exists' => 'The selected office is invalid.',
            'email.exists' => 'The email has already been taken.',
            'phone.exists' => 'The phone number has already been taken.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);
    
        $user = User::findOrFail($id);

        $user->name = $validatedData['name'];
        $user->office_id = $validatedData['office_id'];
        $user->role = $validatedData['role'];
        $user->email = $validatedData['email'];
        $user->address = $validatedData['address'];
        $user->birth_date = $validatedData['birth_date'];
        $user->birth_location = $validatedData['birth_location'];
        $user->phone = $validatedData['phone'];

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']); 
        }

    // Save the user model
    $user->save();
    
        return redirect()->route('user.show', $user->id)->with('success', 'User updated successfully.');
    }
    

    public function destroy($id)
    {
        $user = User::findOrFail($id);
    
        $user->update([
            'status' => Office::STATUS_DELETED,
        ]);
        $user->save();
        $user->delete();

        return redirect()->route('user.index')->with('success', 'User status updated to deleted.');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,trashed',
        ]);

        $user = User::findOrFail($id);

        $user->status = $request->input('status');
        $user->save();
        
        return redirect()->route('user.show', $id)->with('success', 'User status updated successfully.');
    }

    public function edit($id)
    {
        $user = User::with('office')->findOrFail($id);

        $offices = Office::where('status', 'active')->get();

        return view('layouts.master.user.edit', compact('offices', 'user'));
    }

}
