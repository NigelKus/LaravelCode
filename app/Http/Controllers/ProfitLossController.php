<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ProfitLossExport;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        return view('layouts.reports.profit_loss.index');
    }
    
    public function generate(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
    
        $fromdate = $request['from_date'];
        $todate = $request['to_date'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('d/m/Y');
        $createddate = date('j F Y H:i', strtotime('+7 hours'));
        $displayfromdate = Carbon::parse($fromdate)->format('j F Y H:i');
        $displaytodate = Carbon::parse($todate)->format('j F Y H:i');
    
        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
            ->where('code', 'not like', '42%')
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');
        $pendapatanTotal = 0;
        $pendapatan = [];
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                ->sum('amount');
            if ($sum != 0) {
                $pendapatan[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ];
                $pendapatanTotal += abs($sum);  
            }
        }
    
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
            ->where('code', '<=', 8999)
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');
        $bebanTotal = 0;
        $beban = [];
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                ->sum('amount');
            
            if ($sum != 0) {
                $beban[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ];
                $bebanTotal += $sum;  
            }
        }
    
        $HPP = ChartOfAccount::where('code', 4200)
            ->where('status', 'active')
            ->first();
        $codeHPP = ChartOfAccount::where('code', 4200)
            ->where('status', 'active')
            ->pluck('id')->first();
        $totalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
            ->sum('amount');
    
        $saldoAwalPendapatanTotal = 0;
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '<', $fromdate)  
                ->sum('amount');
            if ($sum != 0) {
                $saldoAwalPendapatanTotal += abs($sum);  
            }
        }
    
        $saldoAwalBebanTotal = 0;
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '<', $fromdate)  
                ->sum('amount');
            
            if ($sum != 0) {
                $saldoAwalBebanTotal += $sum;  
            }
        }
    
        $saldoAwalTotalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '<', $fromdate)  
            ->sum('amount');
    
        $saldoAwal = $saldoAwalPendapatanTotal - $saldoAwalBebanTotal - $saldoAwalTotalHPP;
        

        return view('layouts.reports.profit_loss.report', compact(
            'fromdate', 'todate', 'pendapatan', 'beban', 'totalHPP', 'HPP', 
            'displayfromdate', 'displaytodate', 'createddate', 'saldoAwal'
        ));
    }
    
        public function generateProfitLossPDF(Request $request)
    {
        $fromdate = $request['fromdate'];
        $todate = $request['todate'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('d/m/Y');
        $createddate = date('j F Y H:i', strtotime('+7 hours'));
        $displayfromdate = Carbon::parse($fromdate)->format('j F Y H:i');;
        $displaytodate = Carbon::parse($todate)->format('j F Y H:i');
    
        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
            ->where('code', 'not like', '42%')
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');
        
        $pendapatan = [];

        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                
                ->sum('amount');
            
            if ($sum != 0) {
                $pendapatan[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }
    
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
            ->where('code', '<=', 8999)
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');
        
        $beban = [];
    
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                ->sum('amount');
            
            if ($sum != 0) {
                $beban[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ]; 
            }
        }

        $HPP = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')    
        ->first();

        $codeHPP = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')
            ->pluck('id')->first();


        $totalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
            ->sum('amount');

            $HPP = ChartOfAccount::where('code', 4200)
            ->where('status', 'active')
            ->first();
        $codeHPP = ChartOfAccount::where('code', 4200)
            ->where('status', 'active')
            ->pluck('id')->first();
        $totalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
            ->sum('amount');
    
        $saldoAwalPendapatanTotal = 0;
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '<', $fromdate)  
                ->sum('amount');
            if ($sum != 0) {
                $saldoAwalPendapatanTotal += abs($sum);  
            }
        }
    
        $saldoAwalBebanTotal = 0;
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '<', $fromdate)  
                ->sum('amount');
            
            if ($sum != 0) {
                $saldoAwalBebanTotal += $sum;  
            }
        }
    
        $saldoAwalTotalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '<', $fromdate)  
            ->sum('amount');
    
        $saldoAwal = $saldoAwalPendapatanTotal - $saldoAwalBebanTotal - $saldoAwalTotalHPP;


        $pdf = PDF::loadView('layouts.reports.profit_loss.pdf', compact('fromdate', 'todate', 'pendapatan', 'beban', 'HPP', 'date', 'totalHPP', 'displayfromdate', 'displaytodate', 'createddate', 'saldoAwal'));
        return $pdf->stream('profit-loss.pdf');
    }

    public function generateProfitLossExcel(Request $request)
    {
        $fromdate = $request['fromdate'];
        $todate = $request['todate'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('m/d/Y');
    
        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
            ->where('status', 'active')  
            ->where('code', 'not like', '42%')
            ->orderBy('code', 'asc')
            ->pluck('id');
    
        $pendapatan = [];

        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                ->sum('amount');
            
            if ($sum != 0) {
                $pendapatan[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }
    
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
        ->where('status', 'active')  
            ->where('code', '<=', 8999)
            ->orderBy('code', 'asc')
            ->pluck('id');
        
        $beban = [];

        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $fromdate)  
                ->where('date', '<=', $todate) 
                ->sum('amount');
            
            if ($sum != 0) {
                $beban[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ]; 
            }
        }

        $HPP = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
        ->first();

        $codeHPP = ChartOfAccount::where('code', 4200)
            ->where('status', 'active')  
            ->pluck('id')->first();


        $totalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
            ->sum('amount');

            $saldoAwalPendapatanTotal = 0;
            foreach ($pendapatanIds as $id) {
                $sum = Posting::where('account_id', $id)
                    ->where('amount', '<', 0)
                    ->where('date', '<', $fromdate)  
                    ->sum('amount');
                if ($sum != 0) {
                    $saldoAwalPendapatanTotal += abs($sum);  
                }
            }
        
            $saldoAwalBebanTotal = 0;
            foreach ($bebanIds as $id) {
                $sum = Posting::where('account_id', $id)
                    ->where('amount', '>', 0)
                    ->where('date', '<', $fromdate)  
                    ->sum('amount');
                
                if ($sum != 0) {
                    $saldoAwalBebanTotal += $sum;  
                }
            }
        
            $saldoAwalTotalHPP = Posting::where('account_id', $codeHPP)
            ->where('amount', '>', 0)
                ->where('date', '<', $fromdate)  
                ->sum('amount');
        
            $saldoAwal = $saldoAwalPendapatanTotal - $saldoAwalBebanTotal - $saldoAwalTotalHPP;

        $date = date('j F Y H:i', strtotime('+7 hours'));
        $fromdate = Carbon::parse($fromdate)->format('j F Y H:i');;
        $todate = Carbon::parse($todate)->format('j F Y H:i');


        return Excel::download(new ProfitLossExport($fromdate, $todate, $pendapatan, $beban, $HPP, $date, $totalHPP, $saldoAwal), 'Profit Loss.xlsx');
    }
}