<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class TerminationType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }
}
