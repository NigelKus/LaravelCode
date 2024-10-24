<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\PDF;
use App\Exports\GeneralLedgerExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon; 

class BalanceSheetController extends Controller
{

    public function index()
    {
        return view('layouts.reports.balance_sheet.index');
    }

    public function generate(Request $request)
    {
        $month = $request['month'];
        $year = $request['year'];

        $dateString = Carbon::createFromDate($year, $month)->endOfMonth()->toDateString(); 

        if (!checkdate($month, 1, $year)) {
            return back()->withErrors(['month' => 'Invalid month or year']);
        }


        return view('layouts.reports.balance_sheet.report', compact('dateString'));
    }

}