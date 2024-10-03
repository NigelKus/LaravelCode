<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentPurchaseDetail extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes for soft deleting

    protected $table = 'payment_purchase_detail'; // Specify the table name if it's not the plural of the model name

    protected $fillable = [
        'id',
        'payment_id', 
        'invoicepurchase_id', 
        'price', 
        'status', 
    ];

    protected $dates = ['deleted_at']; // Specify the dates that should be treated as Carbon instances

    // Define any relationships here
    public function paymentPurchase()
    {
        return $this->belongsTo(PaymentPurchase::class, 'payment_id'); // Explicitly define the foreign key
    }
    
        public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoicepurchase_id');
    }


}