<?php

namespace App\Services;

use App\Models\Core\Notification;
use App\Models\Core\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to a user
     */
    public function sendNotification(
        User $user, 
        string $title, 
        string $message, 
        string $type = 'info',
        ?string $category = null,
        ?string $actionUrl = null,
        ?array $metadata = null
    ): Notification {
        // Check user's notification preferences
        $preference = $this->getUserPreference($user, $category ?? $type);
        
        if (!$preference || !$preference->is_enabled) {
            return null;
        }

        // Create notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => $category,
            'action_url' => $actionUrl,
            'priority' => $preference->default_priority,
            'metadata' => $metadata,
        ]);

        // Send email if enabled and user has email
        if ($preference->email_enabled && $user->email) {
            $this->sendEmailNotification($user, $notification);
        }

        // Send push notification if enabled (would integrate with push service)
        if ($preference->push_enabled) {
            $this->sendPushNotification($user, $notification);
        }

        return $notification;
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(
        array $users,
        string $title,
        string $message,
        string $type = 'info',
        ?string $category = null,
        ?string $actionUrl = null,
        ?array $metadata = null
    ): array {
        $notifications = [];
        
        foreach ($users as $user) {
            $notification = $this->sendNotification(
                $user, $title, $message, $type, $category, $actionUrl, $metadata
            );
            
            if ($notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * Send benefit request notifications
     */
    public function sendBenefitRequestNotification(
        User $employee,
        User $approver,
        string $action,
        string $requestNumber,
        ?float $amount = null
    ): void {
        // Notify employee about their request status
        $employeeMessage = match($action) {
            'submitted' => "Your benefit request ({$requestNumber}) has been submitted successfully.",
            'approved' => "Your benefit request ({$requestNumber}) has been approved. Amount: {$amount}",
            'rejected' => "Your benefit request ({$requestNumber}) has been rejected.",
            'delivered' => "Your approved benefit ({$requestNumber}) has been delivered.",
            default => "Your benefit request ({$requestNumber}) status has been updated."
        };

        $this->sendNotification(
            $employee,
            'Benefit Request Update',
            $employeeMessage,
            'info',
            'benefits',
            route('employee.benefits.show', $requestNumber)
        );

        // Notify approver about new request
        if ($action === 'submitted') {
            $this->sendNotification(
                $approver,
                'New Benefit Request',
                "A new benefit request ({$requestNumber}) requires your approval.",
                'warning',
                'benefits',
                route('admin.benefits.show', $requestNumber)
            );
        }
    }

    /**
     * Send insurance enrollment notifications
     */
    public function sendInsuranceNotification(
        User $user,
        string $action,
        string $planName,
        ?string $enrollmentId = null
    ): void {
        $message = match($action) {
            'enrolled' => "You have been successfully enrolled in {$planName}.",
            'renewed' => "Your insurance enrollment for {$planName} has been renewed.",
            'terminated' => "Your insurance enrollment for {$planName} has been terminated.",
            'premium_due' => "Premium payment is due for your {$planName} insurance plan.",
            default => "Your insurance status for {$planName} has been updated."
        };

        $this->sendNotification(
            $user,
            'Insurance Update',
            $message,
            'info',
            'insurance',
            $enrollmentId ? route('employee.insurance.show', $enrollmentId) : null
        );
    }

    /**
     * Send score update notifications
     */
    public function sendScoreUpdateNotification(
        User $user,
        float $oldScore,
        float $newScore,
        string $scoreTier
    ): void {
        $change = $newScore - $oldScore;
        $direction = $change > 0 ? 'increased' : ($change < 0 ? 'decreased' : 'remained the same');
        $changeText = $change != 0 ? " ({$direction} by " . abs($change) . " points)" : "";

        $message = "Your employee score has been updated to {$newScore}/100{$changeText}. Current tier: {$scoreTier}.";

        // Only send if score changed significantly or tier changed
        if (abs($change) >= 5 || $this->hasTierChanged($oldScore, $newScore)) {
            $this->sendNotification(
                $user,
                'Score Update',
                $message,
                $change >= 0 ? 'success' : 'warning',
                'performance',
                route('employee.score.show')
            );
        }
    }

    /**
     * Send claim status notifications
     */
    public function sendClaimNotification(
        User $user,
        string $claimNumber,
        string $status,
        ?float $approvedAmount = null
    ): void {
        $message = match($status) {
            'submitted' => "Your insurance claim ({$claimNumber}) has been submitted.",
            'reviewing' => "Your insurance claim ({$claimNumber}) is currently under review.",
            'approved' => "Your insurance claim ({$claimNumber}) has been approved. Amount: {$approvedAmount}",
            'rejected' => "Your insurance claim ({$claimNumber}) has been rejected.",
            'paid' => "Payment for your insurance claim ({$claimNumber}) has been processed.",
            default => "Your insurance claim ({$claimNumber}) status has been updated to: {$status}."
        };

        $this->sendNotification(
            $user,
            'Claim Update',
            $message,
            $status === 'approved' || $status === 'paid' ? 'success' : 'info',
            'insurance',
            route('employee.claims.show', $claimNumber)
        );
    }

    /**
     * Get user's notification preference for a category
     */
    protected function getUserPreference(User $user, string $category): ?NotificationPreference
    {
        return NotificationPreference::where('user_id', $user->id)
            ->where('category', $category)
            ->first();
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(User $user, Notification $notification): void
    {
        try {
            // Would integrate with actual email service
            // For now, just log the notification
            Log::info("Email notification sent to {$user->email}: {$notification->title}");
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
        }
    }

    /**
     * Send push notification
     */
    protected function sendPushNotification(User $user, Notification $notification): void
    {
        try {
            // Would integrate with push notification service (Firebase, OneSignal, etc.)
            Log::info("Push notification sent to {$user->id}: {$notification->title}");
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage());
        }
    }

    /**
     * Check if score tier has changed
     */
    protected function hasTierChanged(float $oldScore, float $newScore): bool
    {
        $oldTier = $this->getScoreTier($oldScore);
        $newTier = $this->getScoreTier($newScore);
        
        return $oldTier !== $newTier;
    }

    /**
     * Get score tier
     */
    protected function getScoreTier(float $score): string
    {
        if ($score >= 85) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 50) return 'medium';
        return 'risk';
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(array $notificationIds, User $user): void
    {
        Notification::whereIn('id', $notificationIds)
            ->where('user_id', $user->id)
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get notifications for a user with pagination
     */
    public function getUserNotifications(User $user, int $perPage = 20)
    {
        return Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications(int $days = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}