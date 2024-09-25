<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch all users from the database
        $users = User::all();

        // Pass the users data to the view at 'layouts.master.user.index'
        return view('layouts.master.user.index', ['users' => $users]);
    }

    public function create()
    {
        // Fetch all users from the database
        $users = User::all();

        // Pass the users data to the view at 'layouts.master.user.index'
        return view('layouts.master.user.create', ['users' => $users]);
    }


}
