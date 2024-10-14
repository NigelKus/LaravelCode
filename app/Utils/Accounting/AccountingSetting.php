<?php

namespace App\Utils\Accounting;

use Auth;
use Carbon\Carbon;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\Posting;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;

/**
 * Class AccountingManager
 * @package App\Utils
 */
class AccountingSetting
{

    const Kas = 1000;
    const Bank = 1100;
    const PiutangUsaha = 1200;
    const UtangUsaha = 2000;
    const Modal = 3000;
    const Pendapatan = 4000;
    const Beban = 5000;
    const Stock = 1300;

}