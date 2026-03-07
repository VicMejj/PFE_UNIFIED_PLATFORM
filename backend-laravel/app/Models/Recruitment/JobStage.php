<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class JobStage extends Model
{
    protected $fillable = [
        'name',
        'sequence',
        'description'
    ];

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
