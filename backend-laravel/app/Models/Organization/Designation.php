<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = [
        'title',
        'code',
        'description',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
