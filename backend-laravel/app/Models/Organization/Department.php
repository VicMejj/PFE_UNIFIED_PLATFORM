<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'branch_id',
        'manager_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
