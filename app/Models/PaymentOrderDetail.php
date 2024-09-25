<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrderDetail extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes for soft deleting

    protected $table = 'payment_order_detail'; // Specify the table name if it's not the plural of the model name

    protected $fillable = [
        'id',
        'payment_id', 
        'invoicesales_id', 
        'price', 
        'status', 
       
    ];

    protected $dates = ['deleted_at']; // Specify the dates that should be treated as Carbon instances

    // Define any relationships here
    public function paymentOrder()
    {
        return $this->belongsTo(PaymentOrder::class); // Define the inverse relationship
    }

    // You can define other relationships or methods as necessary
}
