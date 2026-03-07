<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_value',
        'is_active'
    ];

    protected $casts = [
        'target_value' => 'decimal:5,2',
        'is_active' => 'boolean'
    ];

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class);
    }
}
