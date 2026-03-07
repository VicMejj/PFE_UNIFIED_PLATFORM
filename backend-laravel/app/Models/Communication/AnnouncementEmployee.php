<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class AnnouncementEmployee extends Model
{
    protected $fillable = [
        'announcement_id',
        'employee_id',
        'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee\Employee');
    }
}
