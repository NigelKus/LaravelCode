<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrderDetail extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'sales_payment_detail'; 
    protected $fillable = [
        'id',
        'payment_id', 
        'invoicesales_id', 
        'price', 
        'status', 
    ];

    

    protected $dates = ['deleted_at']; 

    public function paymentOrder()
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_id'); 
    }
    
        public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoicesales_id');
    }

}
