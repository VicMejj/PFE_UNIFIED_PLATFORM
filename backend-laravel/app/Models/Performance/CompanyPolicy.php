<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class CompanyPolicy extends Model
{
    protected $fillable = [
        'title',
        'description',
        'policy_document',
        'effective_date',
        'is_active'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
