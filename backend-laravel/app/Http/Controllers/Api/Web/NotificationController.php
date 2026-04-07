<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\ApiController;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationController extends ApiController
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $this->visibleNotificationsQuery($user)
            ->with([
                'reads' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($notification) => [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->payload['title'] ?? ucfirst(str_replace('_', ' ', $notification->type)),
            'message' => $notification->payload['message'] ?? null,
            'read' => $notification->reads->isNotEmpty(),
            'created_at' => $notification->created_at->toIso8601String(),
            'action' => $notification->payload['action'] ?? null,
        ]);

        return $this->successResponse($notifications, 'Notifications retrieved successfully');
    }

    public function markRead($id)
    {
        $user = auth()->user();
        $notification = $this->visibleNotificationsQuery($user)->findOrFail($id);

        NotificationRead::updateOrCreate(
            [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        return $this->successResponse(null, 'Notification marked as read');
    }

    public function markAllRead()
    {
        $user = auth()->user();
        $notificationIds = $this->visibleNotificationsQuery($user)->pluck('notifications.id');

        if ($notificationIds->isEmpty()) {
            return $this->successResponse(null, 'All notifications marked as read');
        }

        NotificationRead::upsert(
            $notificationIds->map(fn ($notificationId) => [
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all(),
            ['notification_id', 'user_id'],
            ['read_at', 'updated_at']
        );

        return $this->successResponse(null, 'All notifications marked as read');
    }

    public function unreadCount()
    {
        $user = auth()->user();

        $count = $this->visibleNotificationsQuery($user)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return $this->successResponse(['unread_count' => $count], 'Unread notification count retrieved successfully');
    }

    private function visibleNotificationsQuery(User $user): Builder
    {
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
            : [];

        return Notification::query()
            ->where('channel', 'in_app')
            ->where(function ($query) use ($roles, $user) {
                $query->where(function ($broadcastQuery) {
                    $broadcastQuery
                        ->whereNull('target_roles')
                        ->whereNull('target_user_ids');
                });

                $query->orWhereJsonContains('target_user_ids', $user->id);

                foreach ($roles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
            });
    }
}
