<?php

namespace App\Utils\AccountingEvents;

use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\Sales\SalesInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_S02_FinishSalesInvoice extends AE_Base
{
    const TYPE = 'S02';
    const NAME = 'Finish Sales Invoice';
    const REQUIRED_CLASS = SalesInvoice::class;

    public static function buildJournalContent($obj) {
        $journal = AccountingManager::createJournal(
            self::NAME,
            $obj->code,
            self::REQUIRED_CLASS,
            $obj->id,
            null,
        );
        

        $amount = $obj->getTotalPriceAttribute();
        

        AccountingManager::debit($journal,
        AccountingSetting::PiutangUsaha,  // DEBIT :: Piutang Usaha
        $amount,
        '',
        null
    );
    
    AccountingManager::credit($journal,
        AccountingSetting::Pendapatan,  // CREDIT :: Penjualan
        $amount
    );
    

        return $journal;
    }

}