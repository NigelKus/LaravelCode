<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use App\Utils\AccountingEvents\AE_JM1_FinishJournalManualIn;
use App\Utils\AccountingEvents\AE_JM2_FinishJournalManualOut;
use Illuminate\Http\Request;
use App\Models\JournalManual;
use App\Models\ChartOfAccount;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Exports\GeneralLedgerExport;
use Maatwebsite\Excel\Facades\Excel;

class JournalManualController extends Controller
{
    public function index()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->get(); 

        return view('layouts.reports.journal.index', compact('CoAs'));
    }

    public function create()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->orderBy('code', 'asc')->get();

        return view('layouts.reports.journal.create', compact('CoAs'));
    }
    
    public function store(Request $request)
    {
            $coaIds = $request->input('coa_ids', []);
            $amounts = $request->input('amounts', []);
            $total = $request->input('amount');
            
            $calculatedTotal = array_sum($amounts);

            if ($total != $calculatedTotal) {
                return redirect()->back()->withErrors(['error' => 'The amounts are not the same.']);
            }
        
            $filteredData = array_filter(array_map(null, $coaIds, $amounts), function($item) {
                return !is_null($item[0]) && !is_null($item[1]); 
            });
            
            $filteredCoaIds = array_column($filteredData, 0);
            $filteredAmounts = array_column($filteredData, 1);
            
            if (empty($filteredCoaIds)) {
                return redirect()->back()->withErrors(['error' => 'At least one Chart of Account must be selected.']);
            }
            
            $request->merge([
                'coa_ids' => array_values($filteredCoaIds),
                'amounts' => array_values($filteredAmounts)
            ]);
        
            $validatedData = $request->validate([
                'type' => 'required',
                'transaction' => 'required',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'account_id' => 'required|exists'
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'coa_ids.*' => 'required|exists:mstr_coa,code',
                'amounts.*' => 'required|numeric|min:0',
            ]);
        
            $journalManualCode = CodeFactory::generateJournalManualCode();
            DB::beginTransaction();
            $journalManual = JournalManual::create([
                'code' => $journalManualCode,
                'date' => $validatedData['date'],
                'type' => $validatedData['type'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'status' => 'pending',
            ]);

            $journalManual->coa_ids = $validatedData['coa_ids'];
            $journalManual->amounts = $validatedData['amounts'];
            $journalManual->transaction = $validatedData['transaction'];
            $journalManual->amount = $validatedData['amount'];

                // dd($journalManual);
            if($validatedData['type'] = 'in')
            {
                AE_JM1_FinishJournalManualIn::process($journalManual);
            }else{
                AE_JM2_FinishJournalManualOut::process($journalManual);
            }
            // dd($request->all());

            DB::commit();
            return redirect()->route('journal.index')->with('success', 'Journal entry created successfully.');
        
        
    }
}