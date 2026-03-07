<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class IncomeType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
}
