<?php

namespace App\Models\Contract;

use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
