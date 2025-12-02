<?php

namespace App\Http\Traits;

use App\Services\DTOs\ApiErrorResponse;
use Illuminate\Http\JsonResponse;

/**
 * Trait HandlesApiErrors
 * 
 * Provides helper methods for consistent error responses across API controllers.
 */
trait HandlesApiErrors
{
    /**
     * Return standardized API error response
     */
    protected function apiError(
        string $message,
        ?array $errors = null,
        int $status = 500
    ): JsonResponse {
        $response = new ApiErrorResponse(
            success: false,
            message: $message,
            errors: $errors,
            status: $status,
        );

        return $response->toResponse();
    }

    /**
     * Return validation error response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return ApiErrorResponse::validationError($errors, $message)->toResponse();
    }

    /**
     * Return unauthorized error response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return ApiErrorResponse::unauthorized($message)->toResponse();
    }
}
