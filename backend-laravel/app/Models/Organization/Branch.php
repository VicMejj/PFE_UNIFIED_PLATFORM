<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'manager_id',
        'is_active',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
