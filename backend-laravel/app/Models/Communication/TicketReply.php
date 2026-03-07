<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'reply_by',
        'reply_text',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function replyByUser()
    {
        return $this->belongsTo('App\Models\User', 'reply_by');
    }
}
