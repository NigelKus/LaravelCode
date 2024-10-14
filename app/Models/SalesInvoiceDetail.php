<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_invoice_detail';

    const STATUS_DELETED = 'deleted';
    protected $dates = ['date'];

    protected $fillable = [
        'invoicesales_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'salesdetail_id',
    ];

    public function salesinvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoicesales_id');
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

    public function salesOrderDetail()
    {
        return $this->belongsTo(SalesOrderDetail::class, 'salesdetail_id');
    }
    
        public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'salesorder_id');
    }
}
