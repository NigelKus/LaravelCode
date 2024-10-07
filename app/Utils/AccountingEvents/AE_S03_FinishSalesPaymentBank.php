<?php

namespace App\Utils\AccountingEvents;

use App\Utils\Constants;
use App\Utils\SelectHelper;

use App\Models\PaymentOrder;
use App\Models\Sales\SalesInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_S03_FinishSalesPaymentBank extends AE_Base
{
    const TYPE = 'S03';
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
        AccountingSetting::Bank,  // DEBIT :: Bank
            $amount,
            '',
            null,
        );
        

        AccountingManager::credit( $journal,
        AccountingSetting::PiutangUsaha,  // CREDIT :: Piutang
            $amount
        );

        return $journal;
    }

}