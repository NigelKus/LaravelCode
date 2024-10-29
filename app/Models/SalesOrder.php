<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_order';
    const STATUS_DELETED = 'deleted';
    public $timestamps = true;
    protected $dates = ['date'];

    protected $fillable = [
        'code',
        'customer_id',
        'description',
        'status',
        'date',
    ];

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'salesorder_id');
    }

    public function salesorderdetail()
    {
        return $this->hasMany(SalesOrderDetail::class, 'salesorder_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public static function getLatestId()
    {
        return self::orderBy('id', 'desc')->pluck('id')->first();
    }

        public function salesInvoiceDetail()
    {
        return $this->hasMany(SalesInvoiceDetail::class, 'salesorder_id');
    }

    public function salesInvoice()
    {
        return $this->hasMany(SalesInvoice::class, 'salesorder_id');
    }

}
