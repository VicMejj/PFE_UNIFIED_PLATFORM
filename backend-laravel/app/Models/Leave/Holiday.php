<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'holiday_date',
        'description',
        'is_optional'
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_optional' => 'boolean'
    ];

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_optional', false);
    }
}
