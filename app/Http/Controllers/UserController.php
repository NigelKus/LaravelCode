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
        $users = User::all();
        return view('layouts.master.user.index', ['users' => $users]);
    }

    public function create()
    {
        $users = User::all();
        return view('layouts.master.user.create', ['users' => $users]);
    }


}
