<?php

namespace App\Utils\AccountingEvents;

use App\Models\JournalVoucher;
use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_JM1_FinishJournalVoucher extends AE_Base
{
    const TYPE = 'JM1';
    const NAME = 'Finish Journal Voucher';
    const REQUIRED_CLASS = JournalVoucher::class;

    public static function buildJournalContent($obj) {
        $journal = AccountingManager::createJournal(
            self::NAME,
            $obj->code,
            self::REQUIRED_CLASS,
            $obj->id,
            $obj->date,
        );

        foreach($obj->coa_ids1 as $index => $coa_id)
        {
            $amounta = $obj->amounts[$index]; 
        
            AccountingManager::debit(
                $journal,
                $coa_id,          
                $amounta,           
                '',            
                $obj->date,        
            );
        }
        foreach($obj->coa_ids as $index => $coa_id)
        {
            $amounta = $obj->amounts1[$index]; 
        
            AccountingManager::credit(
                $journal,
                $coa_id,          
                $amounta,           
                '',            
                $obj->date,        
            );
        }


        return $journal;
    }

}