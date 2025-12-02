<?php

namespace App\Services\DTOs;

use Illuminate\Http\JsonResponse;

/**
 * API Error Response DTO
 * 
 * Standardized error response format for all API endpoints.
 */
class ApiErrorResponse
{
    public function __construct(
        public bool $success = false,
        public string $message = 'An error occurred',
        public ?array $errors = null,
        public int $status = 500,
    ) {}

    /**
     * Convert DTO to HTTP JSON response
     */
    public function toResponse(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'status' => $this->status,
        ], $this->status);
    }

    /**
     * Create validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
            status: 422,
        );
    }

    /**
     * Create unauthorized error response
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(
            success: false,
            message: $message,
            errors: null,
            status: 401,
        );
    }

    /**
     * Create server error response
     */
    public static function serverError(string $message = 'An error occurred processing your request'): self
    {
        return new self(
            success: false,
            message: $message,
            errors: null,
            status: 500,
        );
    }
}
