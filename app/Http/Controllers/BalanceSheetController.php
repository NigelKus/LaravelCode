<?php

namespace App\Http\Controllers;

use Carbon\Carbon; 
use App\Models\Journal;
use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JournalVoucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Exports\BalanceSheetExport;
use Database\Factories\CodeFactory;
use App\Models\JournalVoucherDetail;
use Maatwebsite\Excel\Facades\Excel;
use App\Utils\AccountingEvents\AE_JM1_FinishJournalVoucher;

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
            
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        $totalModal = abs($totalModal);
        $totalPasiva = $totalPasiva += $totalModal;
        $totalLaba = 0;
        $pendapatanIds = ChartOfAccount::where('code', 'like', '4%')
            ->where('status', 'active')  
            ->where('code', 'not like', '42%')
            ->where('code', 'not like', '43%')
            ->orderBy('code', 'asc')
            ->pluck('id');
        foreach ($pendapatanIds as $id) {
            $sum = Posting::where('account_id', $id)
                ->where('amount', '<', 0)
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
            ->where('date', '<=', $dateStringEnd)
            ->whereNull('deleted_at') 
            ->sum('amount');

        $labaBertahan = ChartOfAccount::where('code', 4300)
        ->where('status', 'active')  
            ->pluck('id')->first();
        $codeLabaBertahan = ChartOfAccount::where('code', 4300)
        ->where('status', 'active')  
        ->first();

        $c = Posting::where('account_id', $labaBertahan)
            ->where('amount', '>', 0)
            ->where('date', '<=', $dateStringEnd)
            ->whereNull('deleted_at') 
            ->sum('amount');

        $totalLabaBerjalan = $b;
        $totalLaba = abs($totalLaba -= $b);
        
        $totalLaba = abs($totalLaba -= $c);
        
        
        $totalPasiva = $totalPasiva += $totalLaba;
        return view('layouts.reports.balance_sheet.report', compact('dateStringDisplay', 
        'totalasset', 'totalUtang', 'totalLaba', 'totalModal', 'codeModal', 'codeLaba', 'totalActiva', 'totalPasiva', 'createddate', 'codeLabaBertahan', 'totalLabaBerjalan'));
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
            ->where('date', '<=', $dateStringEnd) 
            ->sum('amount');
        
            
        $totalLaba = abs($totalLaba -= $b);
        
        $totalPasiva = $totalPasiva += $totalLaba;

        return Excel::download(new BalanceSheetExport($dateStringDisplay, $totalasset, $totalUtang, $totalLaba, $totalModal, $codeModal, $codeLaba, $totalActiva, $totalPasiva, $createddate), 'Balance Sheet.xlsx');
    }

    public function closeBook()
    {
        $labaBerjalan = ChartOfAccount::where('code', 4200)
        ->where('status', 'active')  
            ->pluck('id')->first();

        $labaBertahan = ChartOfAccount::where('code', 4300)
        ->where('status', 'active')  
            ->pluck('id')->first();

        $stock = ChartOfAccount::where('code', 1300)
        ->where('status', 'active')  
            ->pluck('id')->first();

        $b = Posting::where('account_id', $labaBerjalan)
            ->where('amount', '>', 0)
            ->sum('amount');

        $postingsToUpdate  = Posting::where('account_id', $labaBerjalan)->pluck('journal_id');

        foreach ($postingsToUpdate as $id) {
            Posting::where('journal_id', $id)
                ->where(function ($query) use ($labaBerjalan, $stock) {
                    $query->where('account_id', $labaBerjalan)
                        ->orWhere('account_id', $stock);
                })
                ->update(['status' => 'deleted']);
        
            Journal::whereIn('id', [$id])
            ->update(['status' => 'deleted']);
        
        
            Posting::where('journal_id', $id)
                ->where(function ($query) use ($labaBerjalan, $stock) {
                    $query->where('account_id', $labaBerjalan)
                        ->orWhere('account_id', $stock);
                })
                ->delete();
        
            // Ensure you're passing an array to `whereIn`
            Journal::whereIn('id', [$id])
                ->delete();
        }
        

        $journalVoucherCode = CodeFactory::generateJournalVoucherCode();
        DB::beginTransaction();
        try {
            // Create the new journal voucher
            $journalVoucher = JournalVoucher::create([
                'code' => $journalVoucherCode,
                'date' => \Carbon\Carbon::now(),    
                'name' => 'Close Book',
                'description' => 'Close Book',
                'status' => 'pending',
            ]);
            $amounts = [];
            $amounts1 = [];
            $coaIds = [];
            $coaIds1 = [];

            $amounts[] = $b;
            $amounts1[] = $b;
            $coaIds[] = 1300;
            $coaIds1[] = 4300;

            $journalVoucher->coa_ids = $coaIds;
            $journalVoucher->amounts = $amounts;
            $journalVoucher->coa_ids1 = $coaIds1;
            $journalVoucher->amounts1 = $amounts1;
            $journalVoucher->type = 'in';
            $journalacct = AE_JM1_FinishJournalVoucher::process($journalVoucher);           
            
            $postingacct = Posting::where('journal_id', $journalacct->id)->get();
            
            foreach ($postingacct as $index => $a) {
                JournalVoucherDetail::create([
                    'account_id' => $a->account_id,
                    'posting_id' => $a->id,
                    'voucher_id' => $journalVoucher->id,
                    'amount' => $a->amount,
                    'description' => 'Close Book',
                    'status' => 'pending', 
                ]);
            }
    
            DB::commit();
    
            return redirect()->route('balance_sheet.index')->with('success', 'Close Book operation completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}