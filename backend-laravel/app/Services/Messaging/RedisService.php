<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedisService
{
    private string $prefix = 'messaging:';
    private int $ttlProfile = 3600;
    private int $ttlConversation = 1800;
    private bool $redisAvailable = false;

    public function __construct()
    {
        $this->checkRedisConnection();
    }

    private function checkRedisConnection(): void
    {
        try {
            $redis = app('redis')->connection();
            $redis->ping();
            $this->redisAvailable = true;
        } catch (\Exception $e) {
            $this->redisAvailable = false;
            Log::info('Redis unavailable, using cache fallback');
        }
    }

    private function useRedis(): bool
    {
        return $this->redisAvailable;
    }

    public function cacheUserProfile(int $userId, array $profile): void
    {
        $key = $this->prefix . 'profile:' . $userId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::setex($key, $this->ttlProfile, json_encode($profile));
                return;
            } catch (\Exception $e) {
                Log::warning('Redis cache failed, using file cache');
            }
        }
        Cache::put($key, $profile, $this->ttlProfile);
    }

    public function getUserProfile(int $userId): ?array
    {
        $key = $this->prefix . 'profile:' . $userId;
        if ($this->useRedis()) {
            try {
                $data = \Illuminate\Support\Facades\Redis::get($key);
                return $data ? json_decode($data, true) : null;
            } catch (\Exception $e) {
                // Fall through to cache
            }
        }
        return Cache::get($key);
    }

    public function invalidateUserProfile(int $userId): void
    {
        $key = $this->prefix . 'profile:' . $userId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::del($key);
            } catch (\Exception $e) {
                // Fall through
            }
        }
        Cache::forget($key);
    }

    public function cacheConversation(int $userId, int $otherId, array $messages): void
    {
        $key = $this->prefix . 'conversation:' . $userId . ':' . $otherId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::setex($key, $this->ttlConversation, json_encode($messages));
                return;
            } catch (\Exception $e) {}
        }
        Cache::put($key, $messages, $this->ttlConversation);
    }

    public function getCachedConversation(int $userId, int $otherId): ?array
    {
        $key = $this->prefix . 'conversation:' . $userId . ':' . $otherId;
        if ($this->useRedis()) {
            try {
                $data = \Illuminate\Support\Facades\Redis::get($key);
                return $data ? json_decode($data, true) : null;
            } catch (\Exception $e) {}
        }
        return Cache::get($key);
    }

    public function invalidateConversation(int $userId, int $otherId): void
    {
        $key = $this->prefix . 'conversation:' . $userId . ':' . $otherId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::del($key);
            } catch (\Exception $e) {}
        }
        Cache::forget($key);
    }

    public function setUserOnline(int $userId): void
    {
        $key = $this->prefix . 'online';
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::sadd($key, $userId);
                \Illuminate\Support\Facades\Redis::expire($key, 300);
                return;
            } catch (\Exception $e) {}
        }
        $onlineUsers = Cache::get($key, []);
        $onlineUsers[] = $userId;
        Cache::put($key, array_unique($onlineUsers), 300);
    }

    public function setUserOffline(int $userId): void
    {
        $key = $this->prefix . 'online';
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::srem($key, $userId);
                return;
            } catch (\Exception $e) {}
        }
        $onlineUsers = Cache::get($key, []);
        $onlineUsers = array_diff($onlineUsers, [$userId]);
        Cache::put($key, $onlineUsers, 300);
    }

    public function isUserOnline(int $userId): bool
    {
        $key = $this->prefix . 'online';
        if ($this->useRedis()) {
            try {
                return (bool) \Illuminate\Support\Facades\Redis::sismember($key, $userId);
            } catch (\Exception $e) {}
        }
        $onlineUsers = Cache::get($key, []);
        return in_array($userId, $onlineUsers);
    }

    public function getOnlineUsers(): array
    {
        $key = $this->prefix . 'online';
        if ($this->useRedis()) {
            try {
                return \Illuminate\Support\Facades\Redis::smembers($key) ?? [];
            } catch (\Exception $e) {}
        }
        return Cache::get($key, []);
    }

    public function setTyping(int $userId, int $receiverId): void
    {
        $key = $this->prefix . 'typing:' . $receiverId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::setex($key, 5, $userId);
                return;
            } catch (\Exception $e) {}
        }
        Cache::put($key, $userId, 5);
    }

    public function isUserTyping(int $receiverId): ?int
    {
        $key = $this->prefix . 'typing:' . $receiverId;
        if ($this->useRedis()) {
            try {
                $data = \Illuminate\Support\Facades\Redis::get($key);
                return $data ? (int) $data : null;
            } catch (\Exception $e) {}
        }
        return Cache::get($key);
    }

    public function clearTyping(int $receiverId): void
    {
        $key = $this->prefix . 'typing:' . $receiverId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::del($key);
            } catch (\Exception $e) {}
        }
        Cache::forget($key);
    }

    public function incrementUnreadCount(int $userId, int $count = 1): int
    {
        $key = $this->prefix . 'unread:' . $userId;
        if ($this->useRedis()) {
            try {
                return (int) \Illuminate\Support\Facades\Redis::incrby($key, $count);
            } catch (\Exception $e) {}
        }
        $current = Cache::get($key, 0);
        Cache::put($key, $current + $count, 86400);
        return $current + $count;
    }

    public function getUnreadCount(int $userId): int
    {
        $key = $this->prefix . 'unread:' . $userId;
        if ($this->useRedis()) {
            try {
                $count = \Illuminate\Support\Facades\Redis::get($key);
                return (int) ($count ?? 0);
            } catch (\Exception $e) {}
        }
        return (int) Cache::get($key, 0);
    }

    public function resetUnreadCount(int $userId): void
    {
        $this->setUnreadCount($userId, 0);
    }

    public function setUnreadCount(int $userId, int $count): void
    {
        $key = $this->prefix . 'unread:' . $userId;
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::set($key, $count);
                return;
            } catch (\Exception $e) {}
        }
        Cache::put($key, $count, 86400);
    }

    public function publishMessage(array $message): void
    {
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::publish('chat:messages', json_encode($message));
            } catch (\Exception $e) {
                Log::warning('Redis pub/sub failed: ' . $e->getMessage());
            }
        }
    }

    public function publishTyping(int $userId, int $receiverId, bool $isTyping): void
    {
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::publish('chat:typing', json_encode([
                    'sender_id' => $userId,
                    'receiver_id' => $receiverId,
                    'is_typing' => $isTyping,
                ]));
            } catch (\Exception $e) {}
        }
    }

    public function publishStatus(int $userId, bool $isOnline): void
    {
        if ($this->useRedis()) {
            try {
                \Illuminate\Support\Facades\Redis::publish('chat:status', json_encode([
                    'user_id' => $userId,
                    'is_online' => $isOnline,
                ]));
            } catch (\Exception $e) {}
        }
    }
}
