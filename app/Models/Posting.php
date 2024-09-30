<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Posting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acct_postings';

    protected $fillable = [
        'code',
        'account_id',
        'journal_id',
        'amount',
        'description',
        'ref_id_1',
        'ref_type_1',
        'ref_id_2',
        'ref_type_2',
        'ref_id_3',
        'ref_type_3',
        'status',
        'timestamp',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

}