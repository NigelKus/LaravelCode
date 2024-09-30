<?php

namespace App\Utils\AccountingEvents;

use App\Models\Sales\SalesInvoice;
use App\Models\Master\CompanyAccount;
use App\Models\Accounting\Account;
use App\Models\BaseModel;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Constants;
use App\Utils\SelectHelper;
use Illuminate\Support\Facades\DB;

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

        AccountingManager::debit( $journal,
            $journal->id,  // DEBIT :: Piutang Usaha
            $amount,
            '',
            null,
        );
        

        AccountingManager::credit( $journal,
        $journal->id,  // CREDIT :: Penjualan
            $amount
        );

        return $journal;
    }

}