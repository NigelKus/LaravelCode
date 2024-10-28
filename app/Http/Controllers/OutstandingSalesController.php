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
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use App\Http\Controllers\OutStandingSalesController;

class OutStandingSalesController extends Controller
{
    public function index()
    {   
        return view('layouts.reports.outstanding_sales.index');
    }

    public function outstandingOrder(Request $request)
    {
        dd($request->all());
    }
}