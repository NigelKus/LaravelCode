<?php

namespace App\Utils\AccountingEvents;

use Carbon\Carbon;
use App\Utils\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Utils\Accounting\AccountingManager;

class AE_Base
{
    /**
     * Override those constants below
     */
    const TYPE = 'AE';
    const NAME = 'Base';
    const REQUIRED_CLASS = Model::class;
    
    /**
     * Do the accounting event and put it into a Journal
     * Contains `buildJournalContent` function that can be customized
     * Debit and Credit on that journal must be balanced
     *
     * @param $model
     * @return Journal
     */
    public static function process($model) {

        DB::beginTransaction();

        $journal = static::buildJournalContent($model);

        if ( ! AccountingManager::isJournalBalanced($journal)) {
            abort(400, 'Debit and credit is not balanced!');
        }

        DB::commit();

        return $journal;
    }

    /**
     * Reverse latest journal of this Type
     *
     * @param $model
     * @return Journal|null
     */
    public static function reverse($model) {

        $journal = Journal::where('ref_type', get_class($model))
            ->where('ref_id', $model->id)
            ->where('name', static::NAME)
            ->orderBy('created_at', 'DESC')
            ->first();

        if ( ! $journal) {
            // Nothing to reverse
            return null;
        }

        $reversed = AccountingManager::reverseJournal($journal);

        return $reversed;
    }

    /**
     * Dummy Journal content, Override this function
     * and put all Debit Credit transactions here
     *
     * @param $model
     * @return Journal created journal
     */
    public static function buildJournalContent($model) {

        $journal = AccountingManager::createJournal(
            static::NAME,
            'Dummy',
            get_class($model),
            $model->id,
            Carbon::now()
        );

        return $journal;
    }

    public static function deleteAllRelatedJournals($model) {

        $journals = Journal::where('ref_type', get_class($model))
            ->where('ref_id', $model->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        DB::beginTransaction();

        foreach ($journals as $journal) {
            $journal->postings()->delete();
            $journal->delete();
        }

        DB::commit();
    }

    public static function trashedAllRelatedJournals($model) {

        $journals = Journal::where('ref_type', get_class($model))
            ->where('ref_id', $model->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        DB::beginTransaction();

        foreach ($journals as $journal) {
            foreach ($journal->postings as $posting) {
                $posting->status = BaseModel::STATUS_TRASHED;
                $posting->trashed_at = Carbon::now();
                $posting->save();
            }
            $journal->status = BaseModel::STATUS_TRASHED;
            $journal->trashed_at = Carbon::now();
            $journal->save();
        }

        DB::commit();
    }

    public static function refreshJournal($model) {
        static::deleteAllRelatedJournals($model);
        static::process($model);
    }

}