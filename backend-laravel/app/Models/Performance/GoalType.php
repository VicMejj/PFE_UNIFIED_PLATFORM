<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class GoalType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function goalTrackings()
    {
        return $this->hasMany(GoalTracking::class);
    }
}
