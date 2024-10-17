<?php

namespace App\Utils\AccountingEvents;

use App\Models\JournalManual;
use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_JM2_FinishJournalManualOut extends AE_Base
{
    const TYPE = 'JM2';
    const NAME = 'Finish Journal Manual Out';
    const REQUIRED_CLASS = JournalManual::class;

    public static function buildJournalContent($obj) {
        $journal = AccountingManager::createJournal(
            self::NAME,
            $obj->code,
            self::REQUIRED_CLASS,
            $obj->id,
            $obj->date,
        );

        
        if($obj->transaction = 'kas')
        {
            AccountingManager::credit( $journal,
            AccountingSetting::Kas,  // DEBIT :: Kas
                $obj->amount,
                '',
                $obj->date,
            );
        }else{
            AccountingManager::credit( $journal,
            AccountingSetting::Bank,  // DEBIT :: Bank
                $obj->amount,
                '',
                $obj->date,
            );
        }

        foreach($obj->coa_ids as $index => $coa_id)
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

        return $journal;
    }

}