<?php

namespace App\Exceptions;

use App\Services\DTOs\ApiErrorResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Global Exception Handler
 * 
 * Handles all application exceptions and returns standardized API error responses.
 */
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
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return ApiErrorResponse::validationError(
                $exception->errors(),
                'Validation failed'
            )->toResponse();
        }

        // Handle HTTP exceptions
        if ($exception instanceof HttpException) {
            return match ($exception->getStatusCode()) {
                401 => ApiErrorResponse::unauthorized()->toResponse(),
                403 => ApiErrorResponse::serverError('Access denied', null, 403)->toResponse(),
                404 => ApiErrorResponse::serverError('Resource not found', null, 404)->toResponse(),
                429 => ApiErrorResponse::serverError('Too many requests', null, 429)->toResponse(),
                500, 503 => ApiErrorResponse::serverError($exception->getMessage())->toResponse(),
                default => ApiErrorResponse::serverError($exception->getMessage(), null, $exception->getStatusCode())->toResponse(),
            };
        }

        // Default server error response
        return ApiErrorResponse::serverError(
            config('app.debug') ? $exception->getMessage() : 'An error occurred processing your request'
        )->toResponse();
    }
}
