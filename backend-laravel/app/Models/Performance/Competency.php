<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    protected $fillable = [
        'name',
        'description',
        'level'
    ];
}
