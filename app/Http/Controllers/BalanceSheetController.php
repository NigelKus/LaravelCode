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
        $dateStringDisplay = Carbon::createFromDate($year, $month)->endOfMonth()->translatedFormat('j F Y');
        if (!checkdate($month, 1, $year)) {
            return back()->withErrors(['month' => 'Invalid month or year']);
        }

        $dateStringStart = Carbon::createFromDate($year, $month)->startOfMonth()->format('Y-m-d H:i:s'); 
        $dateStringEnd = Carbon::createFromDate($year, $month)->endOfMonth()->format('Y-m-d H:i:s'); 

        $assetIds = ChartOfAccount::where('code', 'like', '1%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');

        $totalActiva = 0;
        $totalPasiva = 0;
        $totalasset = [];

        foreach ($assetIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
                ->where('date', '>=', $dateStringStart)  
                ->where('date', '<=', $dateStringEnd) 
                ->sum('amount');

            $a = abs($sum);
            $totalActiva = $totalActiva += $a;
            
            if ($sum != 0) {
                $totalasset[$id] = [
                    'coa' => ChartOfAccount::find($id),
                    'total' => abs($sum),
                ]; 
            }
        }

        $UtangIds = ChartOfAccount::where('code', 'like', '2%')
        ->where('status', 'active')
        ->orderBy('code', 'asc')
        ->pluck('id');

        $totalUtang = [];

        foreach ($UtangIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
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
            ->orderBy('code', 'asc')
            ->pluck('id');

        $codeModal = ChartOfAccount::where('code', 3000)->first();

        $totalModal = Posting::where('account_id', $id)
            ->where('amount', '>', 0)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');

        $totalPasiva = $totalPasiva += $totalModal;

        $labaId = ChartOfAccount::where('code', 4200)
            ->pluck('id')->first();

        $codeLaba = ChartOfAccount::where('code', 4200)->first();

        $totalLaba = Posting::where('account_id', $labaId)
            ->where('amount', '>', 0)
            ->where('date', '>=', $dateStringStart)  
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
        $totalPasiva = $totalPasiva += $totalLaba;

        return view('layouts.reports.balance_sheet.report', compact('dateStringDisplay', 
        'totalasset', 'totalUtang', 'totalLaba', 'totalModal', 'codeModal', 'codeLaba', 'totalActiva', 'totalPasiva'));
    }

}