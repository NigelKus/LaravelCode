<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\GeneralLedgerExport;
use Maatwebsite\Excel\Facades\Excel;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        $CoAs = ChartOfAccount::where('status', 'active')->get(); 
        return view('layouts.reports.general_ledger.index', compact('CoAs'));
    }
    public function generate(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        $balance = 0;
        $fromdate = $request['from_date'];
        $todate = $request['to_date'];
        $date = date('j F Y H:i', strtotime('+7 hours'));
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $displayfromdate = Carbon::parse($fromdate)->format('j F Y H:i');;
        $displaytodate = Carbon::parse($todate)->format('j F Y H:i');
        if ($request['id'] == null) {
            $coas = ChartOfAccount::where('status', 'active')
            ->orderBy('code', 'asc')
            ->get();
            $results = []; 
            foreach ($coas as $coa) {
                $starting = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '<=', $fromdate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();
    
                $balance = $starting->sum('amount');
                $postings = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '>=', $fromdate);
                    })
                    ->when($todate, function ($query) use ($todate) {
                        return $query->where('date', '<=', $todate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();
                    
                $results[] = [
                    'coa' => $coa,        
                    'starting' => $starting,
                    'balance' => $balance,
                    'postings' => $postings,
                ];
            }
            return view('layouts.reports.general_ledger.report', compact('results', 'displayfromdate', 'displaytodate', 'fromdate', 'todate', 'date'));
        } else {
            $coa = ChartOfAccount::find($request['id']);
            $starting = Posting::where('account_id', $request['id'])
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '<=', $fromdate);
                })
                ->orderBy('date', 'asc')
                ->get();
                
            $balance = $starting->sum('amount');
            $postings = Posting::where('account_id', $request['id'])
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '>=', $fromdate);
                })
                ->when($todate, function ($query) use ($todate) {
                    return $query->where('date', '<=', $todate);
                })
                ->orderBy('date', 'asc')
                ->get();
                $all = false;
            return view('layouts.reports.general_ledger.report', compact('postings', 'balance', 'displayfromdate', 'displaytodate', 'coa', 'fromdate', 'todate', 'date'));
        }
    }
    
    public function generateGeneralLedgerPDF(Request $request)
    {
        $coa_id = $request->input('coa_id');
        $fromdate = $request->input('fromdate');
        $todate = $request->input('todate');
        $title = 'General Ledger Report';
        $date = date('j F Y H:i', strtotime('+7 hours'));

        $displayfromdate = Carbon::parse($fromdate)->format('j F Y H:i');;
        $displaytodate = Carbon::parse($todate)->format('j F Y H:i');

        $results = []; 
        if($coa_id  == null)
        {
            $coas = ChartOfAccount::where('status', 'active')
            ->orderBy('code', 'asc')
            ->get();
            foreach ($coas as $coa) {
                $starting = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '<=', $fromdate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();

                $balance = $starting->sum('amount');

                $postings = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '>=', $fromdate);
                    })
                    ->when($todate, function ($query) use ($todate) {
                        return $query->where('date', '<=', $todate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();

                    $results[] = [
                        'coa' => $coa,        
                        'starting' => $starting,
                        'balance' => $balance,
                        'postings' => $postings,
                    ];
            }
            
            $pdf = PDF::loadView('layouts.reports.general_ledger.pdf', compact('results', 'displayfromdate', 'displaytodate', 'title', 'date', 'fromdate', 'todate'));
            return $pdf->stream('general-ledger-AllCoA.pdf');
        }else{
            $coa = ChartOfAccount::find($coa_id);
    
            $starting = Posting::where('account_id', $coa_id)
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '<=', $fromdate);
                })
                ->orderBy('date', 'asc')
                ->get();
                
            $balance = $starting->sum('amount');
            
            $postings = Posting::where('account_id', $coa_id)
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '>=', $fromdate);
                })
                ->when($todate, function ($query) use ($todate) {
                    return $query->where('date', '<=', $todate);
                })
                ->orderBy('date', 'asc')
                ->get();
                
                
                $pdf = PDF::loadView('layouts.reports.general_ledger.pdf', compact('postings', 'balance', 'coa', 'displayfromdate', 'displaytodate', 'title', 'date', 'fromdate', 'todate'));
                return $pdf->stream('general-ledger-(' . $coa->name . ').pdf');
        }
    }

    public function generateGeneralLedgerExcel(Request $request)
    {
        $coa_id = $request->input('coa_id');
        $fromdate = Carbon::parse($request->input('fromdate'))->format('j F y H:i:s');
        $todate = Carbon::parse($request->input('todate'))->format('j F y H:i:s');
        
        $date = now()->format('j F y H:i:s');
    
        $results = []; 
        if ($coa_id == null) {
            $coas = ChartOfAccount::where('status', 'active')
                ->orderBy('code', 'asc')
                ->get();
            
            foreach ($coas as $coa) {
                $starting = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '<=', $fromdate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();
    
                $balance = $starting->sum('amount');
    
                $postings = Posting::where('account_id', $coa->id)
                    ->with('journal')
                    ->when($fromdate, function ($query) use ($fromdate) {
                        return $query->where('date', '>=', $fromdate);
                    })
                    ->when($todate, function ($query) use ($todate) {
                        return $query->where('date', '<=', $todate);
                    })
                    ->orderBy('date', 'asc')
                    ->get();
    
                $results[] = [
                    'coa' => $coa->name,
                    'coa_code' => $coa->code,
                    'starting_balance' => $balance,
                    'postings' => $postings,
                ];
            }
            $all = true;
            return Excel::download(new GeneralLedgerExport($results, $fromdate, $todate, $date, null, null, null, $all), 'General_Ledger_All_CoA.xlsx');
    
        } else {
            $coa = ChartOfAccount::find($coa_id);
    
            $starting = Posting::where('account_id', $coa_id)
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '<=', $fromdate);
                })
                ->orderBy('date', 'asc')
                ->get();
            
            $balance = $starting->sum('amount');
    
            $postings = Posting::where('account_id', $coa_id)
                ->with('journal')
                ->when($fromdate, function ($query) use ($fromdate) {
                    return $query->where('date', '>=', $fromdate);
                })
                ->when($todate, function ($query) use ($todate) {
                    return $query->where('date', '<=', $todate);
                })
                ->orderBy('date', 'asc')
                ->get();
                
            return Excel::download(new GeneralLedgerExport(null,$fromdate, $todate, $date, $postings, $balance, $coa), 'General_Ledger_' . $coa->name . '.xlsx');
        }
    }

}   