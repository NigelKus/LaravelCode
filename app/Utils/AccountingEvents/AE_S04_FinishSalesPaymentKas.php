<?php

namespace App\Utils\AccountingEvents;

use App\Models\PaymentOrder;
use App\Models\Sales\SalesInvoice;

use App\Utils\Accounting\AccountingManager;
use App\Utils\Constants;
use App\Utils\SelectHelper;
use Illuminate\Support\Facades\DB;

class AE_S04_FinishSalesPaymentKas extends AE_Base
{
    const TYPE = 'S04';
    const NAME = 'Finish Sales Payment';
    const REQUIRED_CLASS = PaymentOrder::class;

    public static function buildJournalContent($obj) {
        $journal = AccountingManager::createJournal(
            self::NAME,
            $obj->code,
            self::REQUIRED_CLASS,
            $obj->id,
            null,
        );

        $amount = $obj->showPriceDetails();
        

        AccountingManager::debit( $journal,
            1000,  // DEBIT :: Kas
            $amount,
            '',
            null,
        );
        

        AccountingManager::credit( $journal,
        1100,  // CREDIT :: Piutang
            $amount
        );

        return $journal;
    }

}