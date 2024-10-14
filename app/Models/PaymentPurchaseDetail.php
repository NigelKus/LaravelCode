<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentPurchaseDetail extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'purchase_payment_detail'; 

    protected $fillable = [
        'id',
        'payment_id', 
        'invoicepurchase_id', 
        'price', 
        'status', 
    ];

    protected $dates = ['deleted_at']; 

    public function paymentPurchase()
    {
        return $this->belongsTo(PaymentPurchase::class, 'payment_id'); 
    }
    
        public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoicepurchase_id');
    }
}