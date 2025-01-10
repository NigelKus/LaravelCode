<?php

namespace App\Http\Controllers;

use App\Exports\BalanceSheetExport;
use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon; 

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        return view('layouts.reports.balance_sheet.index');
    }

    public function generate(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        $month = $request['month'];
        $year = $request['year'];
        $dateStringDisplay = Carbon::createFromDate($year, $month)->endOfMonth()->translatedFormat('j F Y');
        if (!checkdate($month, 1, $year)) {
            return back()->withErrors(['month' => 'Invalid month or year']);
        }
        $createddate = date('j F Y H:i', strtotime('+7 hours'));
        $dateStringStart = Carbon::createFromDate($year, $month)->startOfMonth()->format('Y-m-d H:i:s'); 
        $dateStringEnd = Carbon::createFromDate($year, $month)->endOfMonth()->format('Y-m-d H:i:s'); 
        $totalActiva = 0;
        $totalPasiva = 0;
        $assetIds = ChartOfAccount::where('code', 'like', '1%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');
        $totalasset = [];
        foreach ($assetIds as $id) {
            
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
            $a = $sum;
            $totalActiva = $totalActiva += $a;
            if ($sum != 0) {
                $totalasset[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ]; 
            }
        }
        $UtangIds = ChartOfAccount::where('code', 'like', '2%')
        ->where('status', 'active')  
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');
        $totalUtang = [];
        foreach ($UtangIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
            $a = abs($sum);
            $totalPasiva = $totalPasiva += $a;
            if ($sum != 0) {
                $totalUtang[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }
        $Modalds = ChartOfAccount::where('code', 'like', '3%')
        ->where('status', 'active')  
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');
        $codeModal = ChartOfAccount::where('code', 3000)
        ->where('status', 'active')  
        ->first();
        $totalModal = Posting::where('account_id', $Modalds)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        $totalModal = abs($totalModal);
        $totalPasiva = $totalPasiva += $totalModal;
        $totalLaba = 0;
        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
            ->where('status', 'active')  
            ->where('code', 'not like', '42%')
            ->orderBy('code', 'asc')
            ->pluck('id');
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
                $a = abs($sum);
                $totalLaba = $totalLaba += $a;
        }
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
            ->where('status', 'active')  
            ->where('code', '<=', 8999)
            ->orderBy('code', 'asc')
            ->pluck('id');
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
                $a = $sum;
                $totalLaba = $totalLaba -= $a;
        }
        $labaId = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
            ->pluck('id')->first();
        $codeLaba = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
        ->first();
        $b = Posting::where('account_id', $labaId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        $totalLaba = abs($totalLaba -= $b);
        $totalPasiva = $totalPasiva += $totalLaba;
        return view('layouts.reports.balance_sheet.report', compact('dateStringDisplay', 
        'totalasset', 'totalUtang', 'totalLaba', 'totalModal', 'codeModal', 'codeLaba', 'totalActiva', 'totalPasiva', 'createddate'));
    }


    public function generateBalanceSheetPDF(Request $request)
    {

        $dateStringDisplay = $request['dateStringDisplay'];
        $date = Carbon::parse($dateStringDisplay);
        // dd($date);

        $dateStringStart = $date->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $dateStringEnd = $date->copy()->endOfMonth()->format('Y-m-d H:i:s');
        $createddate = date('j F Y H:i', strtotime('+7 hours'));

        $totalActiva = 0;
        $totalPasiva = 0;

        //Activa
        $assetIds = ChartOfAccount::where('code', 'like', '1%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');

        $totalasset = [];

        foreach ($assetIds as $id) {
            
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

            $a = $sum;

            $totalActiva = $totalActiva += $a;
            
            if ($sum != 0) {
                $totalasset[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ]; 
            }
        }

        //Pasiva

        //Utang Usaha
        $UtangIds = ChartOfAccount::where('code', 'like', '2%')
        
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');
        
        $totalUtang = [];

        foreach ($UtangIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
            
            $a = abs($sum);

            $totalPasiva = $totalPasiva += $a;
            
            if ($sum != 0) {
                $totalUtang[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }

        //Modal
        $Modalds = ChartOfAccount::where('code', 'like', '3%')
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');


        $codeModal = ChartOfAccount::where('code', 3000)->first();

        $totalModal = Posting::where('account_id', $Modalds)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
        $totalModal = abs($totalModal);

        $totalPasiva = $totalPasiva += $totalModal;
        
        //Laba Berjalan
        $totalLaba = 0;

        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
        ->where('status', 'active')  
            ->where('code', 'not like', '42%')
            ->orderBy('code', 'asc')
            ->pluck('id');
        
    
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

                $a = abs($sum);
                $totalLaba = $totalLaba += $a;
        }

    
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
            ->where('code', '<=', 8999)
            ->orderBy('code', 'asc')
            ->pluck('id');
    
    
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

                $a = $sum;
                $totalLaba = $totalLaba -= $a;
        }
        
        $labaId = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
            ->pluck('id')->first();

        $codeLaba = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
        ->first();

        $b = Posting::where('account_id', $labaId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
        $totalLaba = abs($totalLaba -= $b);
        
        $totalPasiva = $totalPasiva += $totalLaba;

        $pdf = PDF::loadView('layouts.reports.balance_sheet.pdf', compact('dateStringDisplay', 
        'totalasset', 'totalUtang', 'totalLaba', 'totalModal', 'codeModal', 'codeLaba', 'totalActiva', 'totalPasiva', 'createddate'));
        return $pdf->stream('balance-sheet.pdf');
    }

    public function generateBalanceSheetExcel(Request $request)
    {
        $dateStringDisplay = $request['dateStringDisplay'];
        $date = Carbon::parse($dateStringDisplay);
        $createddate = date('j F Y H:i', strtotime('+7 hours'));

        $dateStringStart = $date->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $dateStringEnd = $date->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $totalActiva = 0;
        $totalPasiva = 0;

        //Activa
        $assetIds = ChartOfAccount::where('code', 'like', '1%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');

        $totalasset = [];

        foreach ($assetIds as $id) {
            
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

            $a = $sum;

            $totalActiva = $totalActiva += $a;
            
            if ($sum != 0) {
                $totalasset[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => $sum,
                ]; 
            }
        }

        //Pasiva

        //Utang Usaha
        $UtangIds = ChartOfAccount::where('code', 'like', '2%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');
        
        $totalUtang = [];

        foreach ($UtangIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');
            
            $a = abs($sum);

            $totalPasiva = $totalPasiva += $a;
            
            if ($sum != 0) {
                $totalUtang[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }

        //Modal
        $Modalds = ChartOfAccount::where('code', 'like', '3%')
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->pluck('id');


        $codeModal = ChartOfAccount::where('code', 3000)
        ->where('status', 'active')  
        ->first();

        $totalModal = Posting::where('account_id', $Modalds)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
        $totalModal = abs($totalModal);

        $totalPasiva = $totalPasiva += $totalModal;
        
        //Laba Berjalan
        $totalLaba = 0;

        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
        ->where('status', 'active')  
            ->where('code', 'not like', '42%')
            ->orderBy('code', 'asc')
            ->pluck('id');
        
    
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

                $a = abs($sum);
                $totalLaba = $totalLaba += $a;
        }

    
        $bebanIds = ChartOfAccount::where('code', '>=', 5000)
        ->where('status', 'active')  
            ->where('code', '<=', 8999)
            ->orderBy('code', 'asc')
            ->pluck('id');
    
    
        foreach ($bebanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '>', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

                $a = $sum;
                $totalLaba = $totalLaba -= $a;
        }
        
        $labaId = ChartOfAccount::where('code', 4200)
            ->pluck('id')
            ->where('status', 'active')  
            ->first();

        $codeLaba = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
        ->first();

        $b = Posting::where('account_id', $labaId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
        $totalLaba = abs($totalLaba -= $b);
        
        $totalPasiva = $totalPasiva += $totalLaba;

        return Excel::download(new BalanceSheetExport($dateStringDisplay, $totalasset, $totalUtang, $totalLaba, $totalModal, $codeModal, $codeLaba, $totalActiva, $totalPasiva, $createddate), 'Balance Sheet.xlsx');
    }
}