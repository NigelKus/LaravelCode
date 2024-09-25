<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'mstr_purchase';

    public $timestamps = false;

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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
