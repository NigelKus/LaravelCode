<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentPurchase extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'purchase_payment'; 

    protected $fillable = [
        'id',
        'code',
        'supplier_id',
        'description',
        'date',
        'status'
    ];

    protected $dates = ['deleted_at']; 

    public function paymentDetails()
    {
        return $this->hasMany(PaymentPurchaseDetail::class, 'payment_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function showPriceDetails()
    {
        $payments = $this->paymentDetails()->sum('price');  
        return $payments;  
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'ref_id');
    }

}