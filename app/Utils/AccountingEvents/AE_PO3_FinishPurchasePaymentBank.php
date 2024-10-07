<?php

namespace App\Utils\AccountingEvents;

use App\Models\PurchaseInvoice;

use App\Utils\Accounting\AccountingManager;
use App\Utils\Constants;
use App\Utils\SelectHelper;
use Illuminate\Support\Facades\DB;

class AE_PO3_FinishPurchasePaymentBank extends AE_Base
{
    const TYPE = 'P03';
    const NAME = 'Finish Purchase Payment';
    const REQUIRED_CLASS = PurchaseInvoice::class;

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
        2000,  // DEBIT :: Utang Usaha
            $amount,
            '',
            null,
        );
        

        AccountingManager::credit( $journal,
        1200,  // CREDIT :: Bank
            $amount
        );

        return $journal;
    }

}