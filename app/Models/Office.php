<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mstr_office';

    protected $fillable = [
        'name',
        'code',
        'location',
        'phone',
        'opening_date',
        'status',
        'timestamp',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_TRASHED = 'trashed';
    const STATUS_DELETED = 'deleted';

    public function users()
    {
        return $this->hasMany(User::class, 'office_id'); 
    }
}
