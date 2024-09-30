<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    // Specify the table associated with the model  
    protected $table = 'mstr_customer';

    // Disable timestamps if not using created_at and updated_at columns

    // Specify which attributes are mass assignable
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

    // Specify the attributes that should be cast to native types
    protected $casts = [
        'birth_date' => 'date', // Cast birth_date to a Carbon date instance
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';
}
