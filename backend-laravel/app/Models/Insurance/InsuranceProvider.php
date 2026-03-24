<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model
{
    protected $fillable = [
        'name',
        'contact_info',
        'provider_code',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'provider_type',
        'website',
        'insurance_types',
        'payment_terms',
        'is_active',
        'registration_number',
        'created_by'
    ];

    protected $casts = [
        'insurance_types' => 'array',
        'is_active' => 'boolean'
    ];

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('provider_type', $type);
    }
}
