<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_order';

    protected $dates = ['date'];

    protected $fillable = [
        'code',
        'supplier_id',
        'description',
        'status',
        'date',
    ];

    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchaseorder_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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
