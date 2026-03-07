<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
