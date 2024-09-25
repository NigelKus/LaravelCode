<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table associated with the model
    protected $table = 'mstr_product';

    public $timestamps = true;

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';
    
    // Define the attributes that are mass assignable
    protected $fillable = [
        'code',
        'collection',
        'weight',
        'price',
        'stock',
        'description',
        'status',
    ];

    // Optionally, you can define any casting for attributes
    protected $casts = [
        'weight' => 'decimal:2',
        'price' => 'decimal:2',
        'status' => 'string',
    ];

    public function details()
    {
        return $this->hasMany(SalesorderDetail::class, 'product_id');
    }

    // Define any relationships or custom methods as needed
}
