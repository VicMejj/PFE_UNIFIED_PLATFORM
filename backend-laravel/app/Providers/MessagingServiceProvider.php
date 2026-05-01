<?php

namespace App\Providers;

use App\Services\Messaging\RedisService;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisService::class, function ($app) {
            return new RedisService();
        });
    }

    public function boot(): void
    {
        //
    }
}
