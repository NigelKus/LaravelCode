<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acct_journals';

    protected $fillable = [
        'code',
        'date',
        'ref_id',
        'ref_type',
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
        return $this->hasMany(Posting::class, 'journal_id'); // Assuming journal_id is the foreign key in the postings table
    }

    public function salesInvoice()
    {
        return $this->hasMany(SalesInvoice::class, 'ref_id'); // Assuming journal_id is the foreign key in the postings table
    }
    
}