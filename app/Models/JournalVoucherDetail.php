<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucherDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'journal_voucher_detail';

    protected $fillable = [
        'code',
        'account_id',
        'posting_id',
        'voucher_id',
        'description',
        'amount',
        'status',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

    public function voucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function posting()
    {
        return $this->belongsTo(Posting::class, 'posting_id');
    }

}