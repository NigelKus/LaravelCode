<?php

namespace App\Models;

use App\Models\PurchaseInvoiceDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'purchase_invoice';

    const STATUS_DELETED = 'deleted';
    protected $dates = ['date'];

    protected $fillable = [
        'code',
        'purchaseorder_id',
        'supplier_id',
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchaseorder_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function details()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchaseinvoice_id'); // Adjust 'sales_invoice_id' to the actual foreign key in your table
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentPurchaseDetail::class, 'invoicepurchase_id'); // Adjust 'sales_invoice_id' to the actual foreign key in your table
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