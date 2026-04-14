<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForModel($query, string $modelClass, $modelId = null)
    {
        $query->where('auditable_type', $modelClass);
        
        if ($modelId !== null) {
            $query->where('auditable_id', $modelId);
        }
        
        return $query;
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getChangesSummaryAttribute(): array
    {
        $changes = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach (array_keys(array_merge($old, $new)) as $key) {
            if (!isset($old[$key])) {
                $changes[$key] = [
                    'action' => 'added',
                    'old' => null,
                    'new' => $new[$key] ?? null,
                ];
            } elseif (!isset($new[$key])) {
                $changes[$key] = [
                    'action' => 'removed',
                    'old' => $old[$key] ?? null,
                    'new' => null,
                ];
            } elseif ($old[$key] !== $new[$key]) {
                $changes[$key] = [
                    'action' => 'modified',
                    'old' => $old[$key] ?? null,
                    'new' => $new[$key] ?? null,
                ];
            }
        }

        return $changes;
    }

    public function getFormattedEventAttribute(): string
    {
        return match($this->event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'submitted' => 'Submitted',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->event),
        };
    }

    public static function log(string $event, $auditable, ?User $user = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        return static::create([
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'user_id' => $user?->id ?? auth()->id(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}