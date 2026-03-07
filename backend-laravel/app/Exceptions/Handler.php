<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Authorization\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Check if the request wants JSON response
        if ($request->expectsJson()) {
            return $this->handleJsonException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle JSON exceptions for API requests
     */
    private function handleJsonException(Request $request, Throwable $exception)
    {
        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Valid JWT token required',
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Handle authorization exceptions
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - Insufficient permissions',
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_FORBIDDEN);
        }

        // Handle model not found exceptions (404 on show/edit/delete)
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            return response()->json([
                'success' => false,
                'message' => "{$model} not found",
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_NOT_FOUND);
        }

        // Handle route not found exceptions (404)
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_NOT_FOUND);
        }

        // Handle method not allowed exceptions (405)
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed',
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // Handle generic exceptions
        if (env('APP_DEBUG')) {
            // In debug mode, show actual error
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Internal server error',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            // In production, hide error details
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'timestamp' => now()->toIso8601String(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
