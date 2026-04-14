<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function broadcastOn(): array
    {
        $channels = [new Channel('notifications')];

        foreach ((array) $this->notification->target_user_ids as $userId) {
            $channels[] = new PrivateChannel('users.' . $userId . '.notifications');
        }

        foreach ((array) $this->notification->target_roles as $role) {
            $channels[] = new PrivateChannel('roles.' . $role . '.notifications');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'payload' => $this->notification->payload,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
