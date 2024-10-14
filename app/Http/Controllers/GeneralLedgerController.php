<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->get(); 

        return view('layouts.reports.general_ledger.index', compact('CoAs'));
    }
    

    public function generate(Request $request)
    {
        $balance = 0;
        $fromdate = $request['from_date'];
        $todate = $request['to_date'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);

        $coa = ChartOfAccount::find($request['id']); 

        $starting = Posting::where('account_id', $request['id'])
            ->with('journal')
            ->when($fromdate, function ($query, $fromdate) {
                return $query->where('date', '<=', $fromdate);
            })
            ->orderBy('date', 'asc')
            ->get();
    
        $balance = $starting->sum('amount');
        
        $postings = Posting::where('account_id', $request['id'])
            ->with('journal')
            ->when($fromdate, function ($query, $fromdate) {
                return $query->where('date', '>=', $fromdate);
            })
            ->when($todate, function ($query, $todate) {
                return $query->where('date', '<=', $todate);
            })
            ->orderBy('date', 'asc')
            ->get();
        

        return view('layouts.reports.general_ledger.report', compact('postings', 'balance', 'fromdate', 'todate', 'coa'));
    }
    
}