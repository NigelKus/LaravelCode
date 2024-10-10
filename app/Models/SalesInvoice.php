<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesInvoice extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table name
    protected $table = 'sales_invoice';

    const STATUS_DELETED = 'deleted';
    protected $dates = ['date'];

    // Define fillable fields
    protected $fillable = [
        'code',
        'salesorder_id',
        'customer_id',
        'description',
        'status',
        'date',
        'due_date',
    ];

        public function getTotalPriceAttribute()
    {
        return $this->details->sum(function ($detail) {
            return $detail->price * $detail->quantity; 
        });
    }

    public function calculatePriceRemaining()  
    {  
        $totalPrice = $this->getTotalPriceAttribute();  
        $payments = $this->paymentDetails()->sum('price');  
        
        return $totalPrice - $payments;  
    } 
    
    public function showPriceDetails()
    {
        $payments = $this->paymentDetails()->sum('price');  
        return $payments;  
    }

    // Define relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'salesorder_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

// SalesInvoice.php
    public function details()
    {
        return $this->hasMany(SalesInvoiceDetail::class, 'invoicesales_id'); // Adjust 'sales_invoice_id' to the actual foreign key in your table
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentOrderDetail::class, 'invoicesales_id'); // Adjust 'sales_invoice_id' to the actual foreign key in your table
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'ref_id');
    }
    


    /**
     * Get the latest sales order ID.
     *
     * @return int|null
     */
    public static function getLatestId()
    {
        return self::orderBy('id', 'desc')->pluck('id')->first();
    }

    
}
