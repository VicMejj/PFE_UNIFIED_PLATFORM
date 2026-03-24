<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = [
        'employee_id',
        'award_type_id',
        'title',
        'gift',
        'cash_price',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'cash_price' => 'decimal:2'
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
