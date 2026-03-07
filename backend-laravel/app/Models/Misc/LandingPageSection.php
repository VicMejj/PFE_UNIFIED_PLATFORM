<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    protected $fillable = [
        'section_name',
        'section_heading',
        'section_description',
        'section_image',
        'section_order',
        'is_active'
    ];

    protected $casts = [
        'section_order' => 'integer',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->orderBy('section_order');
    }
}
