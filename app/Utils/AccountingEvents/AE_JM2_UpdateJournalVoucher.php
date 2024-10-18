<?php

namespace App\Utils\AccountingEvents;

use App\Models\Journal;
use App\Utils\Constants;

use App\Utils\SelectHelper;
use App\Models\JournalManual;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Utils\Accounting\AccountingManager;
use App\Utils\Accounting\AccountingSetting;

class AE_JM2_UpdateJournalVoucher extends AE_Base
{
    const TYPE = 'JM2';
    const NAME = 'Update Journal Voucher';
    const REQUIRED_CLASS = JournalManual::class;

    public static function buildJournalContent($obj) {

        $journal = $obj->journal_id;

        $journal = Journal::find($journal);

        foreach($obj->coa_ids1 as $index => $coa_id)
        {
            $amounta = $obj->amounts1[$index]; 
        
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
            $amounta = $obj->amounts[$index]; 
        
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