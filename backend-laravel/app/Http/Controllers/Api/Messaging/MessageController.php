<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Events\Messaging\MessageDeliveredEvent;
use App\Events\Messaging\MessageReadEvent;
use App\Events\Messaging\TypingEvent;
use App\Events\Messaging\UserStatusEvent;
use App\Http\Controllers\Controller;
use App\Jobs\Messaging\ProcessMessageJob;
use App\Models\Messaging\Message;
use App\Models\User;
use App\Services\Messaging\RedisService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function __construct(
        private RedisService $redisService
    ) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'nullable|string|max:5000|required_without:attachment',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $receiver = User::findOrFail($request->receiver_id);

        if (! $this->canAccessMessaging($user)) {
            return response()->json(['error' => 'Messaging access is restricted to administrators, HR, and managers.'], 403);
        }

        if (!$this->isValidConversation($user, $receiver)) {
            return response()->json(['error' => 'Invalid conversation. Only administrators, HR, and managers can communicate.'], 403);
        }

        $attachmentData = null;
        if ($request->hasFile('attachment')) {
            $attachmentData = $this->handleAttachment($request->file('attachment'));
        }

        $senderRole = $this->getUserRole($user);
        $receiverRole = $this->getUserRole($receiver);

        $messageData = [
            'message_id' => uniqid('msg_'),
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'sender_role' => $senderRole,
            'receiver_role' => $receiverRole,
            'content' => trim((string) $request->input('content', '')),
            'attachment_path' => $attachmentData['path'] ?? null,
            'attachment_type' => $attachmentData['type'] ?? null,
            'attachment_name' => $attachmentData['name'] ?? null,
            'created_at' => now()->toIso8601String(),
        ];

        dispatch(new ProcessMessageJob($messageData));

        $this->redisService->publishMessage([
            'type' => 'message_sent',
            'message' => $messageData,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message queued for delivery',
            'data' => $messageData,
        ], 202);
    }

    public function getConversation(Request $request, int $userId): JsonResponse
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $otherUser = User::findOrFail($userId);

        if (! $this->canAccessMessaging($currentUser) || ! $this->isValidConversation($currentUser, $otherUser)) {
            return response()->json(['error' => 'Invalid conversation.'], 403);
        }
        
        $query = Message::conversation($currentUserId, $userId)
            ->orderBy('created_at', 'asc');
        
        if ($since = $request->query('since')) {
            $query->where('created_at', '>', $since);
        }
        
        $messages = $query->get();

        $this->markMessagesAsDelivered($messages, $userId, $currentUserId);

        $messages = $messages
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'content' => $msg->content,
                    'attachment_path' => $msg->attachment_path,
                    'attachment_type' => $msg->attachment_type,
                    'attachment_name' => $msg->attachment_name,
                    'status' => $msg->status,
                    'created_at' => $msg->created_at->toIso8601String(),
                    'is_mine' => $msg->sender_id === Auth::id(),
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'has_more' => $request->has('since'),
            'unread_count' => $this->redisService->getUnreadCount($currentUserId),
        ]);
    }

    /**
     * Lightweight polling endpoint - returns only new messages since timestamp
     */
    public function getNewMessages(int $userId): JsonResponse
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $otherUser = User::findOrFail($userId);

        if (! $this->canAccessMessaging($currentUser) || ! $this->isValidConversation($currentUser, $otherUser)) {
            return response()->json(['error' => 'Invalid conversation.'], 403);
        }

        $afterId = max(0, (int) request()->query('after_id', 0));

        $messages = Message::conversation($currentUserId, $userId)
            ->when($afterId > 0, fn($q) => $q->where('id', '>', $afterId))
            ->orderBy('created_at', 'asc')
            ->get();

        $this->markMessagesAsDelivered($messages, $userId, $currentUserId);

        $messages = $messages
            ->map(fn($msg) => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'content' => $msg->content,
                'attachment_path' => $msg->attachment_path,
                'attachment_type' => $msg->attachment_type,
                'attachment_name' => $msg->attachment_name,
                'status' => $msg->status,
                'created_at' => $msg->created_at->toIso8601String(),
                'is_mine' => $msg->sender_id === $currentUserId,
            ]);

        $unreadCount = $this->redisService->getUnreadCount($currentUserId);

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'unread_count' => $unreadCount,
        ]);
    }

    public function getConversations(): JsonResponse
    {
        $userId = Auth::id();
        $user = Auth::user();

        if (! $this->canAccessMessaging($user)) {
            return response()->json(['error' => 'Messaging access is restricted to administrators, HR, and managers.'], 403);
        }

        $conversations = Message::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
        ->selectRaw('
            CASE 
                WHEN sender_id = ? THEN receiver_id 
                ELSE sender_id 
            END as other_user_id
        ', [$userId])
        ->groupBy('other_user_id')
        ->selectRaw('MAX(created_at) as last_message_time')
        ->selectRaw('COUNT(*) as message_count')
        ->orderByDesc('last_message_time')
        ->get();

        $result = [];
        foreach ($conversations as $conv) {
            $otherUserId = $conv->other_user_id;
            $otherUser = User::find($otherUserId);
            if (!$otherUser) continue;
            if (! $this->isValidConversation($user, $otherUser)) continue;

            $lastMessage = Message::where(function ($query) use ($userId, $otherUserId) {
                $query->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })->latest()->first();

            $profile = $this->redisService->getUserProfile($otherUserId);
            if (!$profile) {
                $profile = [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'email' => $otherUser->email,
                    'avatar_url' => $otherUser->avatar_url,
                    'role' => $this->getUserRole($otherUser),
                ];
                $this->redisService->cacheUserProfile($otherUserId, $profile);
            }

            $unread = Message::where('sender_id', $otherUserId)
                ->where('receiver_id', $userId)
                ->where('status', '!=', 'read')
                ->count();

            $result[] = [
                'user' => $profile,
                'last_message' => $lastMessage ? [
                    'content' => $lastMessage->content,
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                    'is_mine' => $lastMessage->sender_id === $userId,
                    'status' => $lastMessage->status,
                ] : null,
                'unread_count' => $unread,
                'is_online' => $this->redisService->isUserOnline($conv->other_user_id),
            ];
        }

        return response()->json([
            'success' => true,
            'conversations' => $result,
            'online_users' => $this->redisService->getOnlineUsers(),
        ]);
    }

    public function getAvailableContacts(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canAccessMessaging($user)) {
            return response()->json(['error' => 'Messaging access is restricted to administrators, HR, and managers.'], 403);
        }

        $search = trim((string) $request->query('search', ''));

        $contacts = User::query()
            ->with('roles')
            ->whereKeyNot(Auth::id())
            ->where(function (Builder $query) {
                $query->whereHas('roles', fn (Builder $builder) => $builder->whereIn('name', ['admin', 'rh', 'hr', 'manager']));
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->filter(fn (User $contact) => $this->isValidConversation($user, $contact))
            ->map(function (User $contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'avatar_url' => $contact->avatar_url,
                    'role' => $this->getUserRole($contact),
                    'is_online' => $this->redisService->isUserOnline($contact->id),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }

    public function markAsRead(int $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);
        
        if ($message->receiver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->markAsRead();
        $this->syncUnreadCount(Auth::id());

        $this->redisService->publishMessage([
            'type' => 'message_read',
            'message_id' => $messageId,
            'reader_id' => Auth::id(),
        ]);
        broadcast(new MessageReadEvent($messageId, Auth::id(), $message->sender_id));

        return response()->json(['success' => true]);
    }

    public function markConversationAsRead(int $userId): JsonResponse
    {
        $messages = Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('status', '!=', 'read')
            ->get();

        foreach ($messages as $message) {
            $message->markAsRead();
            broadcast(new MessageReadEvent($message->id, Auth::id(), $message->sender_id));
        }

        $this->syncUnreadCount(Auth::id());

        return response()->json(['success' => true]);
    }

    public function markConversationAsDelivered(int $userId): JsonResponse
    {
        $currentUser = Auth::user();
        $otherUser = User::findOrFail($userId);

        if (! $this->canAccessMessaging($currentUser) || ! $this->isValidConversation($currentUser, $otherUser)) {
            return response()->json(['error' => 'Invalid conversation.'], 403);
        }

        $messages = Message::query()
            ->where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('status', 'sent')
            ->get();

        $this->markMessagesAsDelivered($messages, $userId, Auth::id());

        return response()->json(['success' => true]);
    }

    public function setTyping(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'is_typing' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->is_typing) {
            $this->redisService->setTyping(Auth::id(), $request->receiver_id);
        } else {
            $this->redisService->clearTyping($request->receiver_id);
        }

        $this->redisService->publishTyping(Auth::id(), $request->receiver_id, $request->is_typing);
        broadcast(new TypingEvent(Auth::id(), (int) $request->receiver_id, (bool) $request->is_typing));

        return response()->json(['success' => true]);
    }

    public function getOnlineStatus(): JsonResponse
    {
        $userId = Auth::id();
        $this->redisService->setUserOnline($userId);
        broadcast(new UserStatusEvent($userId, true, $this->getMessagingPeerUserIds($userId)));

        return response()->json([
            'success' => true,
            'is_online' => true,
            'online_users' => $this->redisService->getOnlineUsers(),
        ]);
    }

    public function setOfflineStatus(): JsonResponse
    {
        $this->redisService->setUserOffline(Auth::id());
        
        $this->redisService->publishStatus(Auth::id(), false);
        broadcast(new UserStatusEvent(Auth::id(), false, $this->getMessagingPeerUserIds(Auth::id())));

        return response()->json(['success' => true]);
    }

    public function getUnreadCount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'unread_count' => $this->redisService->getUnreadCount(Auth::id()),
        ]);
    }

    public function downloadAttachment(int $messageId)
    {
        $message = Message::findOrFail($messageId);

        if (! $message->attachment_path) {
            return response()->json(['error' => 'No attachment'], 404);
        }

        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (! Storage::disk('public')->exists($message->attachment_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download(
            $message->attachment_path,
            $message->attachment_name,
            ['Content-Type' => $message->attachment_type]
        );
    }

    private function canAccessMessaging(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'rh', 'hr', 'manager']);
    }

    private function isValidConversation(User $sender, User $receiver): bool
    {
        return $this->canAccessMessaging($sender)
            && $this->canAccessMessaging($receiver)
            && (int) $sender->id !== (int) $receiver->id;
    }

    private function getUserRole(User $user): string
    {
        $roles = $user->getRoleNames();

        if ($roles->contains('admin')) {
            return 'admin';
        }
        
        if ($roles->contains('hr') || $roles->contains('rh') || $roles->contains('rh_manager')) {
            return 'hr';
        }
        if ($roles->contains('manager')) {
            return 'manager';
        }
        
        return 'unknown';
    }

    private function handleAttachment($file): array
    {
        $path = $file->store('message-attachments', 'public');
        
        return [
            'path' => $path,
            'type' => $file->getMimeType(),
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function syncUnreadCount(int $userId): void
    {
        $this->redisService->setUnreadCount(
            $userId,
            Message::unread($userId)->count()
        );
    }

    private function markMessagesAsDelivered($messages, int $senderId, int $receiverId): void
    {
        foreach ($messages as $message) {
            if (
                (int) $message->sender_id !== $senderId
                || (int) $message->receiver_id !== $receiverId
                || $message->status !== 'sent'
            ) {
                continue;
            }

            $message->markAsDelivered();
            broadcast(new MessageDeliveredEvent($message->id, $receiverId, $senderId));
        }
    }

    /**
     * @return array<int>
     */
    private function getMessagingPeerUserIds(int $userId): array
    {
        return Message::query()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->get(['sender_id', 'receiver_id'])
            ->flatMap(fn (Message $message) => [$message->sender_id, $message->receiver_id])
            ->filter(fn (int $id) => $id !== $userId)
            ->unique()
            ->values()
            ->all();
    }
}
