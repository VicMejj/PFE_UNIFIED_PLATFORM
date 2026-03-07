<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_to',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function assignedToUser()
    {
        return $this->belongsTo('App\Models\User', 'assigned_to');
    }

    public function createdByUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
