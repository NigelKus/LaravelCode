<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'journal_voucher';

    public $coa_ids = [];
    public $amounts = [];
    public $coa_ids1 = [];
    public $amounts1 = [];
    public $type;
    public $journal_id;

    protected $fillable = [
        'code',
        'date',
        'type',
        'name',
        'description',
        'status',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'ref_id');
    }

    public function detail()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'voucher_id');
    }

}