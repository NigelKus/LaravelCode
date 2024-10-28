<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Journal;
use App\Models\Posting;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JournalVoucher;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;
use App\Models\JournalVoucherDetail;
use App\Utils\AccountingEvents\AE_JM1_FinishJournalVoucher;
use App\Utils\AccountingEvents\AE_JM2_UpdateJournalVoucher;

class JournalVoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalVoucher::where('status', 'pending');

    
        $statuses = ['pending', 'completed'];
    
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
    
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
    
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
    
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('date', $request->date);
        }
    
        if ($request->has('sort')) {
            if ($request->sort == 'recent') {
                $query->orderBy('date', 'desc');
            } elseif ($request->sort == 'oldest') {
                $query->orderBy('date', 'asc'); 
            }
        }

        $perPage = $request->get('perPage', 10);
        $journalVouchers = $query->paginate($perPage);

        return view('layouts.reports.journal.index', compact('journalVouchers', 'statuses'));
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
            
        
            $journalVoucherCode = CodeFactory::generateJournalVoucherCode();
            DB::beginTransaction();
            $journalVoucher = JournalVoucher::create([
                'code' => $journalVoucherCode,
                'date' => $validatedData['date'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'status' => 'pending',
            ]);

            $journalVoucher->coa_ids = $validatedData['coa_ids'];
            $journalVoucher->amounts = $validatedData['amounts'];
            $journalVoucher->coa_ids1 = $validatedData['coa_ids1'];
            $journalVoucher->amounts1 = $validatedData['amounts1'];
            $journalVoucher->type = 'in';


            $journalacct = AE_JM1_FinishJournalVoucher::process($journalVoucher);

            $postingacct = Posting::where('journal_id', $journalacct->id)->get();
            
            foreach ($postingacct as $index => $a) {
                JournalVoucherDetail::create([
                    'account_id' => $a->account_id,
                    'posting_id' => $a->id,
                    'voucher_id' => $journalVoucher->id,
                    'amount' => $a->amount,
                    'description' => isset($descriptions[$index]) ? $descriptions[$index] : null,
                    'status' => 'pending',
                ]);
            }
            DB::commit();
            return redirect()->route('journal.show', $journalVoucher->id)->with('success', 'Journal entry created successfully.');
    }

    // public function show($id)
    // {
    //     $journalVoucher  = JournalVoucher::with('journal')->where('id', $id)->first();

    //     $details = JournalVoucherDetail::with('account')->where('voucher_id', $journalVoucher->id)->get();


    //     return view('layouts.reports.journal.show',compact('journalVoucher', 'details'));
    // }

        public function show($id)
    {
        $journalVoucher = JournalVoucher::with('journal')->findOrFail($id); 

        $journal = Journal::withTrashed()->where('ref_id', $id)->first();

        $details = JournalVoucherDetail::with(['account' => function ($query) {
            $query->withTrashed();
        }])->where('voucher_id', $journalVoucher->id)->get();
        


        // dd($details);

        return view('layouts.reports.journal.show', compact('journalVoucher', 'details', 'journal'));
    }


    public function edit($id)
    {
        $CoAs = ChartOfAccount::where('status', 'active')->orderBy('code', 'asc')->get();

        $kasbank = ChartOfAccount::where('status', 'active')
            ->orderBy('code', 'asc')
            ->get();

        $journalVoucher = JournalVoucher::where('id', $id)->first();

        $debitDetails = JournalVoucherDetail::where('voucher_id', $journalVoucher->id)
                                            ->where('amount', '>', 0)
                                            ->get();

        $creditDetails = JournalVoucherDetail::where('voucher_id', $journalVoucher->id)
                                            ->where('amount', '<', 0)
                                            ->get();

        

        return view('layouts.reports.journal.edit', compact('CoAs', 'kasbank', 'journalVoucher','debitDetails', 'creditDetails'));
    }

    public function destroy($id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id); 
    
        $details = JournalVoucherDetail::where('voucher_id', $journalVoucher->id)->get();
    
        $journal = Journal::where('ref_id', $journalVoucher->id)->first();
    
        foreach ($details as $detail) {
            
            $posting = Posting::where('id', $detail->posting_id)->first();
            
            if ($posting) {
                $posting->update(['status'=>'deleted']);
                $posting->delete();
            }
            $detail->update(['status'=>'deleted']);
            $detail->delete();
        }
    
        if ($journal) {
            $journal->update(['status'=>'deleted']);
            $journal->delete();
        }
    
        $journalVoucher->update(['status' => 'deleted']);
        $journalVoucher->delete();
    
        return redirect()->route('journal.index')->with('success', 'Journal Voucher deleted successfully');
    }
    

    public function update(Request $request)
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
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coa_ids.*' => 'required|exists:mstr_coa,code',
            'amounts.*' => 'required|numeric|min:0',
            'descriptions.*' => 'nullable|string',
            'coa_ids1.*' => 'required|exists:mstr_coa,code',
            'amounts1.*' => 'required|numeric|min:0',
            'descriptions1.*' => 'nullable|string',
            'journal_id' => 'exists:journal_voucher,id'
        ]);
        $descriptions = array_merge(
            $validatedData['descriptions'],
            $validatedData['descriptions1']
        );
        DB::beginTransaction();

        $journalVoucher = JournalVoucher::findOrFail($validatedData['journal_id']);

        $details = JournalVoucherDetail::where('voucher_id', $journalVoucher->id)->get();

        // dd($details);

        foreach ($details as $detail) {
            $posting = Posting::where('id', $detail->posting_id)->first();
            
            if ($posting) {
                $posting->update([
                    'status' => 'deleted',
                ]);
                $posting->delete();
            }

            $detail->update([
                'status' => 'deleted',
            ]);
            $detail->delete();
        }
        

        $journalVoucher->update([
            'date' => $validatedData['date'],
            'description' => $validatedData['description'],
            'name' => $validatedData['name'],
        ]);

        $journal = Journal::where('ref_id', $journalVoucher->id)->first();

        $journalVoucher->coa_ids = $validatedData['coa_ids'];
        $journalVoucher->amounts = $validatedData['amounts'];
        $journalVoucher->coa_ids1 = $validatedData['coa_ids1'];
        $journalVoucher->amounts1 = $validatedData['amounts1'];
        $journalVoucher->journal_id = $journal->id;

        $journalacct = AE_JM2_UpdateJournalVoucher::process($journalVoucher);

        if ($journal) {
            $journal->date = Carbon::parse($request['date']);
            $journal->save();
        }

        $postingacct = Posting::where('journal_id', $journalacct->id)->get();
            
        foreach ($postingacct as $index => $a) {
            JournalVoucherDetail::create([
                'account_id' => $a->account_id,
                'posting_id' => $a->id,
                'voucher_id' => $journalVoucher->id,
                'amount' => $a->amount,
                'description' => isset($descriptions[$index]) ? $descriptions[$index] : null,
                'status' => 'pending',
            ]);
        }
        DB::commit();

        // DB::rollBack();
        
        return redirect()->route('journal.show', $journalVoucher->id)->with('success', 'Journal Voucher updated successfully.');
    }
}