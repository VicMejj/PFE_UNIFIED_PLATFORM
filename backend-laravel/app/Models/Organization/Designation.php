<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'level',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
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
