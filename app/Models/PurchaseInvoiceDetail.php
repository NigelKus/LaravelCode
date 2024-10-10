<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table name
    protected $table = 'purchase_invoice_detail';

    const STATUS_DELETED = 'deleted';
    protected $dates = ['date'];

    // Define fillable fields
    protected $fillable = [
        'purchaseinvoice_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'salesdetail_id',
    ];

    // Define relationships
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchaseinvoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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

    public function purchaseOrderDetail()
    {
        return $this->belongsTo(PurchaseOrderDetail::class, 'purchasedetail_id');
    }
    
        public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchaseorder_id');
    }
}
