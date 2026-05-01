<?php

namespace App\Events\Messaging;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->message['receiver_id']),
            new PrivateChannel('user.' . $this->message['sender_id']),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'new_message',
            'message' => $this->message,
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-message';
    }
}
