<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'leave_code',
        'maximum_days',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
