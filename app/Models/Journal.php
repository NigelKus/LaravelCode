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
        return $this->hasMany(Posting::class, 'journal_id'); 
    }

    public function salesInvoice()
    {
        return $this->hasMany(SalesInvoice::class, 'ref_id'); 
    }

    public function purchaseInvoice()
    {
        return $this->hasMany(PurchaseInvoice::class, 'ref_id'); 
    }
    
    public function paymentOrder()
    {
        return $this->hasMany(PaymentOrder::class, 'ref_id'); 
    }

    public function paymentPurchase()
    {
        return $this->hasMany(PaymentPurchase::class, 'ref_id'); 
    }

    public function journalManual()
    {
        return $this->hasMany(JournalManual::class, 'ref_id'); 
    }
}