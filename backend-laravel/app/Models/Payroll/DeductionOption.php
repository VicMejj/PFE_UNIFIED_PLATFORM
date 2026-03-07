<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class DeductionOption extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function saturationDeductions()
    {
        return $this->hasMany(SaturationDeduction::class);
    }
}
