<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function predict(array $data)
    {
        return Http::post(env('DJANGO_AI_URL'), $data)->json();
    }
}
