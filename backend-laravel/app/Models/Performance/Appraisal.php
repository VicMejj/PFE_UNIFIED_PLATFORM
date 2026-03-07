<?php

namespace App\Models\Performance;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    protected $fillable = [
        'employee_id',
        'appraisal_year',
        'performance_type_id',
        'rating',
        'comments',
        'reviewed_by',
        'review_date',
        'status'
    ];

    protected $casts = [
        'rating' => 'decimal:5,2',
        'review_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceType()
    {
        return $this->belongsTo(PerformanceType::class);
    }

    public function reviewedByUser()
    {
        return $this->belongsTo('App\Models\User', 'reviewed_by');
    }
}
