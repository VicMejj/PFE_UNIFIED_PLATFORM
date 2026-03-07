<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'template_data',
        'is_active'
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
