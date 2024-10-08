<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes for soft deleting

    protected $table = 'sales_payment'; // Specify the table name if it's not the plural of the model name

    protected $fillable = [
        'id',
        'code',
        'customer_id',
        'description',
        'date',
        'status'
    ];



    protected $dates = ['deleted_at']; // Specify the dates that should be treated as Carbon instances

    // Define any relationships here
    public function paymentDetails()
    {
        return $this->hasMany(PaymentOrderDetail::class, 'payment_id'); // Example relationship
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
