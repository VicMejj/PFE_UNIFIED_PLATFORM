<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Payer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code'
    ];

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
}
