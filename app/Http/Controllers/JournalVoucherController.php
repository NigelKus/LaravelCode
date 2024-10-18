<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JournalVoucher;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Models\JournalVoucherDetail;
use App\Utils\AccountingEvents\AE_JM1_FinishJournalVoucher;

class JournalVoucherController extends Controller
{
    public function index()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->get(); 

        return view('layouts.reports.journal.index', compact('CoAs'));
    }

    public function create()
    {
        $CoAs = ChartOfAccount::where('status', 'active')->orderBy('code', 'asc')->get();

        $kasbank = ChartOfAccount::where('status', 'active')
            ->orderBy('code', 'asc')
            ->get();

        return view('layouts.reports.journal.create', compact('CoAs', 'kasbank'));
    }
    
    public function store(Request $request)
    {
            // dd($request->all());
            $coaIds = $request->input('coa_ids', []);
            $amounts = $request->input('amounts', []);
            $descriptions = $request->input('descriptions', []);
            $coaIds1 = $request->input('coa_ids1', []);
            $amounts1 = $request->input('amounts1', []);
            $descriptions1 = $request->input('descriptions1', []);
            
            $calculatedTotal = array_sum($amounts);
            $calculatedTotal1 = array_sum($amounts1);


            if ($calculatedTotal != $calculatedTotal1) {
                return redirect()->back()->withErrors(['error' => 'The amounts are not the same.']);
            }
            $filteredData = array_filter(array_map(null, $coaIds, $amounts, $descriptions), function($item) {
                return !empty($item[0]) && !empty($item[1]); 
            });
            
            $filteredData1 = array_filter(array_map(null, $coaIds1, $amounts1, $descriptions1), function($item) {
                return !empty($item[0]) && !empty($item[1]); 
            });
            
            $filteredCoaIds = array_column($filteredData, 0);      
            $filteredAmounts = array_column($filteredData, 1);      
            $filteredDescriptions = array_column($filteredData, 2);  
            $filteredCoaIds1 = array_column($filteredData1, 0);     
            $filteredAmounts1 = array_column($filteredData1, 1);     
            $filteredDescriptions1 = array_column($filteredData1, 2); 
            
            if (empty($filteredCoaIds) && empty($filteredCoaIds1)) {
                return redirect()->back()->withErrors(['error' => 'At least one Chart of Account must be selected on each side.'])->withInput();
            }
            
            $request->merge([
                'coa_ids' => array_values($filteredCoaIds),
                'amounts' => array_values($filteredAmounts),
                'descriptions' => array_values($filteredDescriptions),
                'coa_ids1' => array_values($filteredCoaIds1),
                'amounts1' => array_values($filteredAmounts1),
                'descriptions1' => array_values($filteredDescriptions1),
            ]);
        
            $validatedData = $request->validate([
                'type' => 'required',
                'date' => 'required|date',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'coa_ids.*' => 'required|exists:mstr_coa,code',
                'amounts.*' => 'required|numeric|min:0',
                'descriptions.*' => 'nullable|string',
                'coa_ids1.*' => 'required|exists:mstr_coa,code',
                'amounts1.*' => 'required|numeric|min:0',
                'descriptions1.*' => 'nullable|string',
            ]);
            $descriptions = array_merge(
                $validatedData['descriptions'],
                $validatedData['descriptions1']
            );
            
        
            $journalManualCode = CodeFactory::generateJournalVoucherCode();
            DB::beginTransaction();
            $journalManual = JournalVoucher::create([
                'code' => $journalManualCode,
                'date' => $validatedData['date'],
                'type' => $validatedData['type'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'status' => 'pending',
            ]);

            $journalManual->coa_ids = $validatedData['coa_ids'];
            $journalManual->amounts = $validatedData['amounts'];
            $journalManual->coa_ids1 = $validatedData['coa_ids1'];
            $journalManual->amounts1 = $validatedData['amounts1'];
            $journalManual->type = $validatedData['type'];

            $journalacct = AE_JM1_FinishJournalVoucher::process($journalManual);

            $postingacct = Posting::where('journal_id', $journalacct->id)->get();
            
            foreach ($postingacct as $index => $a) {
                JournalVoucherDetail::create([
                    'account_id' => $a->account_id,
                    'posting_id' => $a->id,
                    'voucher_id' => $journalManual->id,
                    'amount' => $a->amount,
                    'description' => isset($descriptions[$index]) ? $descriptions[$index] : null,
                    'status' => 'pending',
                ]);
            }


            DB::commit();
            return redirect()->route('journal.index')->with('success', 'Journal entry created successfully.');
        
        
    }
}