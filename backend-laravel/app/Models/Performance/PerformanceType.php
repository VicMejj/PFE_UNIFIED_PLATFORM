<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class PerformanceType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class);
    }
}
