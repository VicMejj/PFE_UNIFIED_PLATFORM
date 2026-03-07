<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
