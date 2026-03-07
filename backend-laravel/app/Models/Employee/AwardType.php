<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class AwardType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function awards()
    {
        return $this->hasMany(Award::class);
    }
}
