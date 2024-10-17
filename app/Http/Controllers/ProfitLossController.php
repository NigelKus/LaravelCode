<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\PDF;
use App\Exports\GeneralLedgerExport;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLossController extends Controller
{
    public function index()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->get(); 

        return view('layouts.reports.profit_loss.index', compact('CoAs'));
    }
    
}