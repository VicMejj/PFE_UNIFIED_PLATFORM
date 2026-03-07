<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceDependent extends Model
{
    protected $fillable = [
        'enrollment_id',
        'dependent_name',
        'relationship',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'status',
        'created_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date'
    ];

    public function enrollment()
    {
        return $this->belongsTo(InsuranceEnrollment::class);
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function getAge()
    {
        return now()->diffInYears($this->date_of_birth);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}
