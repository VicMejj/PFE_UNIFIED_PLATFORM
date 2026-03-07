<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure all API requests expect JSON
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // Add security headers to all responses
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('API-Version', 'v1');

        // Add JWT token info if available
        try {
            if ($token = JWTAuth::getToken()) {
                $response->headers->set('Authorization', 'Bearer ' . $token);
            }
        } catch (\Exception $e) {
            // Token not available or invalid - no action needed
        }

        return $response;
    }
}
