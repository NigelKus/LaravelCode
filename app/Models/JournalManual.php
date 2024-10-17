<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalManual extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'journal_manual';

    public $coa_ids = [];
    public $amounts = [];
    public $transaction;
    public $amount;

    protected $fillable = [
        'code',
        'date',
        'type',
        'name',
        'description',
        'status',
        'timestamp',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'ref_id');
    }

}