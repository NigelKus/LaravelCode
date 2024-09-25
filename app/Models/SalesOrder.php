<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table name
    protected $table = 'mstr_salesorder';
    const STATUS_DELETED = 'deleted';
    public $timestamps = true;
    protected $dates = ['date'];

    // Define fillable fields
    protected $fillable = [
        'code',
        'customer_id',
        'description',
        'status',
        'date',
    ];

    // Define relationships
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
}
