<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\PDF;
use App\Exports\ProfitLossExport;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLossController extends Controller
{
    public function index()
    {

        return view('layouts.reports.profit_loss.index');
    }
    
    public function generate(Request $request)
    {
        $fromdate = $request['from_date'];
        $todate = $request['to_date'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
    
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
        // dd($totalHPP);

        return view('layouts.reports.profit_loss.report', compact('fromdate', 'todate', 'pendapatan', 'beban', 'totalHPP', 'HPP'));
    }
    

        public function generateProfitLossPDF(Request $request)
    {
        $fromdate = $request['fromdate'];
        $todate = $request['todate'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('m/d/Y');
    
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


        $pdf = PDF::loadView('layouts.reports.profit_loss.pdf', compact('fromdate', 'todate', 'pendapatan', 'beban', 'HPP', 'date', 'totalHPP'));
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


        return Excel::download(new ProfitLossExport($fromdate, $todate, $pendapatan, $beban, $HPP, $date, $totalHPP), 'Profit Loss.xlsx');
    }
}