<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'actor_id',
        'target_roles',
        'target_user_ids',
        'payload',
        'channel',
        'read_at',
        'dedup_key',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $notification) {
            event(new NotificationCreated($notification));
        });
    }
}
