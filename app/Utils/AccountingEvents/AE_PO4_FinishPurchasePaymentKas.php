<?php

namespace App\Utils\AccountingEvents;

use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\PaymentPurchase;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_PO4_FinishPurchasePaymentKas extends AE_Base
{
    const TYPE = 'P04';
    const NAME = 'Finish Purchase Payment';
    const REQUIRED_CLASS = PaymentPurchase::class;

    public static function buildJournalContent($obj) {
        $journal = AccountingManager::createJournal(
            self::NAME,
            $obj->code,
            self::REQUIRED_CLASS,
            $obj->id,
            $obj->date,
        );

        $amount = $obj->showPriceDetails();
        

        AccountingManager::debit( $journal,
        AccountingSetting::UtangUsaha,  // DEBIT :: Utang Usaha
            $amount,
            '',
            $obj->date,
        );
        

        AccountingManager::credit( $journal,
        AccountingSetting::Kas,  // CREDIT :: Kas
            $amount,
            '',
            $obj->date,
        );

        return $journal;
    }

}