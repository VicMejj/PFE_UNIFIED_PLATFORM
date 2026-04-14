<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceAssignmentHistory extends Model
{
    protected $fillable = [
        'insurance_assignment_id',
        'changed_by',
        'action',
        'previous_values',
        'new_values',
        'reason',
    ];

    protected $casts = [
        'previous_values' => 'array',
        'new_values' => 'array',
    ];

    public function assignment()
    {
        return $this->belongsTo(InsuranceAssignment::class, 'insurance_assignment_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}