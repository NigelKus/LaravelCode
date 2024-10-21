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
    
        $pendapatanIds = ChartOfAccount::where('code', '>=', 4000)
            ->where('code', '<', 5000)
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
            ->where('code', '<', 8999)
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

        $stockId = ChartOfAccount::where('code', 1300)->pluck('id')->first();

        $persediaanAwal = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '<', $fromdate) 
            ->sum('amount');

        $persediaanAkhir = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
        ->sum('amount');


        $stock = abs($persediaanAwal - $persediaanAkhir);

        return view('layouts.reports.profit_loss.report', compact('fromdate', 'todate', 'pendapatan', 'beban', 'stock'));
    }
    

        public function generateProfitLossPDF(Request $request)
    {
        $fromdate = $request['fromdate'];
        $todate = $request['todate'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('m/d/Y');
    
        $pendapatanIds = ChartOfAccount::where('code', '>=', 4000)
            ->where('code', '<', 5000)
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
            ->where('code', '<', 8999)
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

        $stockId = ChartOfAccount::where('code', 1300)->pluck('id')->first();

        $persediaanAwal = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '<', $fromdate) 
            ->sum('amount');

        $persediaanAkhir = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
        ->sum('amount');

        $stock = abs($persediaanAwal - $persediaanAkhir);


        $pdf = PDF::loadView('layouts.reports.profit_loss.pdf', compact('fromdate', 'todate', 'pendapatan', 'beban', 'stock', 'date'));
        return $pdf->stream('profit-loss.pdf');
    }

    public function generateProfitLossExcel(Request $request)
    {
        $fromdate = $request['fromdate'];
        $todate = $request['todate'];
        $fromdate = str_replace('T', ' ', $fromdate);
        $todate = str_replace('T', ' ', $todate);
        $date = date('m/d/Y');
    
        $pendapatanIds = ChartOfAccount::where('code', '>=', 4000)
            ->where('code', '<', 5000)
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
            ->where('code', '<', 8999)
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

        $stockId = ChartOfAccount::where('code', 1300)->pluck('id')->first();

        $persediaanAwal = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '<', $fromdate) 
            ->sum('amount');

        $persediaanAkhir = Posting::where('account_id', $stockId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $fromdate)  
            ->where('date', '<=', $todate) 
        ->sum('amount');

        $stock = abs($persediaanAwal - $persediaanAkhir);


        return Excel::download(new ProfitLossExport($fromdate, $todate, $pendapatan, $beban, $stock, $date), 'Profit Loss.xlsx');
    }
}