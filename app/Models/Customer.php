<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mstr_customer';

    protected $fillable = [
        'name',
        'code',
        'sales_category',
        'address',
        'phone',
        'description',
        'birth_date',
        'birth_city',
        'email',
    'status',
        'timestamp',
    ];

    protected $casts = [
        'birth_date' => 'date', 
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';
}
