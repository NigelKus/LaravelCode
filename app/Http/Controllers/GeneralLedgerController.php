<?php

namespace App\Http\Controllers;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        return view('layouts.reports.general_ledger.index');
    }
}