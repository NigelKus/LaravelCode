<?php

namespace App\Utils\Accounting;

use Auth;
use Carbon\Carbon;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\Posting;
use Illuminate\Support\Facades\DB;
use Database\Factories\CodeFactory;

/**
 * Class AccountingManager
 * @package App\Utils
 */
class AccountingManager
{
    public static function getAccountByCode($accountCode) {
        $account = ChartOfAccount::where('code', $accountCode)->first();
        return optional($account);
    }
    
    /**
     * Create new Journal
     * @param $name
     * @param $description
     * @param $refClass
     * @param $refId
     * @param $date Carbon
     * @return Journal
     */
    public static function createJournal($name, $description, $refClass, $refId, Carbon $date = null) {
        
        if (is_null($date)) {
            $date = Carbon::now();
        }


        $journal = new Journal;
        $journal->code = CodeFactory::transactionCode();
        $journal->date = $date;
        $journal->ref_type = $refClass;
        $journal->ref_id = $refId;
        $journal->name = $name;
        $journal->description = $description;
        $journal->save();
        return $journal;
    }


    /**
     * Check if all Postings in a Journal
     * Satisfy the requirement: Total Debit == Total Credit
     * @param Journal $journal
     * @return bool
     */
    public static function isJournalBalanced(Journal $journal) {
        $sum = Posting::where('journal_id', $journal->id)
            ->sum('amount');
        return ($sum == 0);
    }

    /**
     * Add Debit Posting to a Journal
     * @param Journal $journal
     * @param $accountCode
     * @param $amount
     * @param $description
     * @param $date Carbon
     * @return array
     */
    public static function debit(Journal $journal, $accountCode, $amount, $description = '', Carbon $date = null) {
        if ($amount == 0) {
            return [
                'success' => false
            ];
        }

        if (is_null($date)) {
            $date = $journal->date;
        }
        
        $account = ChartOfAccount::where('code', $accountCode)->first();

        if (! $account) {
            abort(400, 'Account Code [' . $accountCode . '] not found');
        }

        $posting = new Posting;
        $posting->date = $date;
        $posting->account_id = $account->id;
        $posting->journal_id = $journal->id;
        $posting->amount = $amount;
        $posting->description = $description;
        $posting->save();

        return [
            'success' => true,
            'posting' => $posting,
            'account' => $account
        ];
    }


    /**
     * Add Credit Posting to a Journal
     * @param Journal $journal
     * @param $accountCode
     * @param $amount
     * @param $description
     * @param $date Carbon
     * @return array
     */
    public static function credit(Journal $journal, $accountCode, $amount, $description = '', Carbon $date = null) {
        if ($amount == 0) {
            return [
                'success' => true
            ];
        }

        if (is_null($date)) {
            $date = $journal->date;
        }

        $account = ChartOfAccount::where('code', $accountCode)->first();

        if (! $account) {
            abort(400, 'Account Code [' . $accountCode . '] not found');
        }

        $posting = new Posting;
        $posting->date = $date;
        $posting->account_id = $account->id;
        $posting->journal_id = $journal->id;
        $posting->amount = - $amount;
        $posting->description = $description;
        $posting->save();

        return [
            'success' => true,
            'posting' => $posting,
            'account' => $account
        ];
    }


    /**
     * Reverse journal
     * @param Journal $journal
     * @return Journal $journal|null
     */
    public static function reverseJournal(Journal $journal) {

        DB::beginTransaction();

        $newJournal = self::createJournal(
            'JURNAL BALIK: ' . $journal->name,
            $journal->description,
            $journal->ref_type,
            $journal->ref_id,
            Carbon::parse($journal->date),
        );

        foreach ($journal->postings as $posting) {
            $newPosting = new Posting;
            $newPosting->date = $posting->date;
            $newPosting->office_id = $posting->office_id;
            $newPosting->account_id = $posting->account_id;
            $newPosting->journal_id = $newJournal->id;
            $newPosting->amount = - $posting->amount;
            $newPosting->description = $posting->description;
            $newPosting->save();
        }

        if ( ! AccountingManager::isJournalBalanced($newJournal)) {
            abort(400, 'Debit and credit is not balanced!');
        }

        DB::commit();

        return $newJournal;
    }
}