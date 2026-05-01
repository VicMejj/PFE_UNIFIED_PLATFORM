<?php

namespace App\Jobs\Messaging;

use App\Events\Messaging\NewMessageEvent;
use App\Models\Messaging\Message;
use App\Services\Messaging\RedisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private array $messageData
    ) {
        $this->onQueue('messages');
    }

    public function handle(RedisService $redisService): void
    {
        try {
            $message = Message::create([
                'sender_id' => $this->messageData['sender_id'],
                'receiver_id' => $this->messageData['receiver_id'],
                'sender_role' => $this->messageData['sender_role'],
                'receiver_role' => $this->messageData['receiver_role'],
                'content' => $this->messageData['content'],
                'attachment_path' => $this->messageData['attachment_path'] ?? null,
                'attachment_type' => $this->messageData['attachment_type'] ?? null,
                'attachment_name' => $this->messageData['attachment_name'] ?? null,
                'status' => 'sent',
            ]);

            $redisService->invalidateConversation(
                $this->messageData['sender_id'],
                $this->messageData['receiver_id']
            );
            $redisService->invalidateConversation(
                $this->messageData['receiver_id'],
                $this->messageData['sender_id']
            );

            $redisService->incrementUnreadCount($this->messageData['receiver_id']);

            $redisService->publishMessage([
                'type' => 'new_message',
                'message' => $message->toArray(),
            ]);
            broadcast(new NewMessageEvent($message->fresh()->toArray()));

            Log::info('Message processed and stored', ['message_id' => $message->id]);
        } catch (\Exception $e) {
            Log::error('Failed to process message', [
                'error' => $e->getMessage(),
                'data' => $this->messageData,
            ]);
            throw $e;
        }
    }
}
