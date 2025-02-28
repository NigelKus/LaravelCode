<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mstr_coa';

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'status',
        'timestamp',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

    public function postings()
    {
        return $this->hasMany(Posting::class, 'account_id'); 
    }

    public function voucherDetail()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'account_id'); 
    }


}