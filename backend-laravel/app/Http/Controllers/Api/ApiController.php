<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ApiController extends Controller
{
    /**
     * Standard success response wrapper with JWT security headers.
     *
     * @param  mixed  $data
     * @param  string|null  $message
     * @param  int  $code
     * @param  array|null  $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $code = Response::HTTP_OK,
        ?array $headers = null
    ) {
        $payload = [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! is_null($message)) {
            $payload['message'] = $message;
        }

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        // Add authentication token info if authenticated
        if (auth()->check()) {
            $payload['user'] = [
                'id' => auth()->id(),
                'email' => auth()->user()->email,
            ];
        }

        $response_headers = array_merge(
            $headers ?? [],
            [
                'Content-Type' => 'application/json',
                'X-API-Version' => 'v1',
            ]
        );

        return response()->json($payload, $code, $response_headers);
    }

    /**
     * Standard error response wrapper with JWT security headers.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|null  $errors
     * @param  array|null  $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $code = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
        ?array $headers = null
    ) {
        $payload = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! is_null($errors) && ! empty($errors)) {
            $payload['errors'] = $errors;
        }

        $response_headers = array_merge(
            $headers ?? [],
            [
                'Content-Type' => 'application/json',
                'X-API-Version' => 'v1',
            ]
        );

        return response()->json($payload, $code, $response_headers);
    }

    /**
     * Validation error response with detailed field errors.
     *
     * @param  ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(ValidationException $exception)
    {
        return $this->errorResponse(
            'Validation failed',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception->errors()
        );
    }

    /**
     * Unauthorized response (401).
     *
     * @param  string|null  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(?string $message = null)
    {
        return $this->errorResponse(
            $message ?? 'Unauthorized - Valid JWT token required',
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Forbidden response (403).
     *
     * @param  string|null  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(?string $message = null)
    {
        return $this->errorResponse(
            $message ?? 'Forbidden - Insufficient permissions',
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Not found response (404).
     *
     * @param  string  $resource
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Resource')
    {
        return $this->errorResponse(
            "{$resource} not found",
            Response::HTTP_NOT_FOUND
        );
    }
}
