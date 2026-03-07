<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Model;

class IpRestrict extends Model
{
    protected $fillable = [
        'ip_address',
        'description',
        'is_restricted',
        'created_by'
    ];

    protected $casts = [
        'is_restricted' => 'boolean'
    ];

    public function createdByUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
}
