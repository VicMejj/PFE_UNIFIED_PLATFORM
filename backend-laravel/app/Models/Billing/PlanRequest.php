<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlanRequest extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'requested_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'requested_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
