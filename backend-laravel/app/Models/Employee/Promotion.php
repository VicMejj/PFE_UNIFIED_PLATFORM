<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'employee_id',
        'promotion_date',
        'from_designation_id',
        'to_designation_id',
        'promotion_type',
        'reason',
        'remarks'
    ];

    protected $casts = [
        'promotion_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
