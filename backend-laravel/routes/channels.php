<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.notifications', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('roles.{role}.notifications', function ($user, string $role) {
    return method_exists($user, 'hasRole') ? $user->hasRole($role) : false;
});
