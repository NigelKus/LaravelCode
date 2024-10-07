<?php

namespace App\Utils\AccountingEvents;

use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_PO2_FinishPurchaseInvoice extends AE_Base
{
    const TYPE = 'P02';
    const NAME = 'Finish Purchase Invoice';
    const REQUIRED_CLASS = PurchaseInvoice::class;

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
        AccountingSetting::Pendapatan,  // DEBIT :: Pendapatan
            $amount,
            '',
            null,
        );
        

        AccountingManager::credit( $journal,
        AccountingSetting::UtangUsaha,  // CREDIT :: Utang Usaha
            $amount
        );

        return $journal;
    }

}