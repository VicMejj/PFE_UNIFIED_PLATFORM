<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = [
        'employee_id',
        'award_type_id',
        'award_date',
        'description',
        'gift_amount',
        'gift_item'
    ];

    protected $casts = [
        'award_date' => 'date',
        'gift_amount' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function awardType()
    {
        return $this->belongsTo(AwardType::class);
    }
}
