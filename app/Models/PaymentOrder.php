<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'sales_payment'; 

    protected $fillable = [
        'id',
        'code',
        'customer_id',
        'description',
        'date',
        'status'
    ];



    protected $dates = ['deleted_at'];

    public function paymentDetails()
    {
        return $this->hasMany(PaymentOrderDetail::class, 'payment_id'); 
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
