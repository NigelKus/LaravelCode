<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes for soft deleting

    protected $table = 'mstr_payment'; // Specify the table name if it's not the plural of the model name

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
        return $this->hasMany(PaymentOrderDetail::class); // Example relationship
    }
}
