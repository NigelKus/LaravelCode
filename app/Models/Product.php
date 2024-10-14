<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mstr_product';

    public $timestamps = true;

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';
    
    protected $fillable = [
        'code',
        'collection',
        'weight',
        'price',
        'stock',
        'description',
        'status',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'price' => 'decimal:2',
        'status' => 'string',
    ];

    public function details()
    {
        return $this->hasMany(SalesorderDetail::class, 'product_id');
    }

}
