<?php

namespace App\Events\Messaging;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<int> $targetUserIds
     */
    public function __construct(
        public int $userId,
        public bool $isOnline,
        public array $targetUserIds,
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $targetUserId) => new PrivateChannel('user.' . $targetUserId),
            $this->targetUserIds
        );
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user-status';
    }
}
