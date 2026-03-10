<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;

trait CallsDjangoAI
{
    /**
     * Makes an authenticated POST request to the Django AI backend,
     * forwarding the current user's JWT token from the Authorization header.
     */
    protected function djangoPost(string $path, array $payload = []): \Illuminate\Http\Client\Response
    {
        $djangoUrl = env('DJANGO_AI_URL', 'http://localhost:8001');
        $jwtToken = request()->header('Authorization'); // "Bearer <token>"

        return Http::withHeaders([
            'Authorization' => $jwtToken,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout(10)->post($djangoUrl . $path, $payload);
    }

    /**
     * Helper to forward a Django response as a Laravel JSON response.
     */
    protected function forwardDjangoResponse(\Illuminate\Http\Client\Response $response)
    {
        if ($response->successful()) {
            return $this->successResponse($response->json());
        }

        return response()->json([
            'error' => 'AI Service error',
            'detail' => $response->json()
        ], $response->status());
    }
}
