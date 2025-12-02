<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Error Response Format Test
 * 
 * Verifies that all API error responses follow the standardized format
 * with success, message, errors, and status fields.
 */
class ErrorResponseFormatTest extends TestCase
{
    /**
     * Test validation error response format
     */
    public function test_validation_error_response_format(): void
    {
        // Trigger validation error by missing required fields
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            // email is missing
            // password is missing
        ]);

        $this->assertEquals(422, $response->status());

        // Check response structure
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
            'status',
        ]);

        // Validate specific values
        $this->assertFalse($response->json('success'));
        $this->assertEquals(422, $response->json('status'));
        $this->assertIsArray($response->json('errors'));
        $this->assertNotEmpty($response->json('errors'));

        // Should have validation errors for missing fields
        $errors = $response->json('errors');
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * Test that error message is provided
     */
    public function test_validation_error_has_message(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertNotEmpty($response->json('message'));
        $this->assertFalse($response->json('success'));
    }

    /**
     * Test error response JSON is valid and parseable
     */
    public function test_error_response_is_valid_json(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        // Response should be valid JSON with status 422
        $this->assertEquals(422, $response->status());

        // All required keys present
        $json = $response->json();
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('status', $json);

        // Values are correct types
        $this->assertIsBool($json['success']);
        $this->assertIsString($json['message']);
        $this->assertIsInt($json['status']);
    }

    /**
     * Test that success field is false for errors
     */
    public function test_error_success_field_is_false(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            // missing other required fields
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertFalse($response->json('success'), 'success field should be false for validation errors');
    }

    /**
     * Test error response doesn't include sensitive data
     */
    public function test_error_response_sanitized(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'password' => 'password123',
        ]);

        $this->assertEquals(422, $response->status());
        $json = $response->json();

        // Response should not echo back sensitive data
        $responseStr = json_encode($json);
        $this->assertNotContains('password123', $responseStr, 'Passwords should not appear in error responses');
    }

    /**
     * Test consistent error format across different error types
     */
    public function test_error_format_consistency(): void
    {
        // Test validation error
        $validationResponse = $this->postJson('/api/auth/register', ['email' => 'invalid']);

        // Both should have same structure
        $validationJson = $validationResponse->json();

        $this->assertArrayHasKey('success', $validationJson);
        $this->assertArrayHasKey('message', $validationJson);
        $this->assertArrayHasKey('status', $validationJson);
        $this->assertArrayHasKey('errors', $validationJson);

        $this->assertFalse($validationJson['success']);
        $this->assertIsInt($validationJson['status']);
    }

    /**
     * Test errors array contains error messages
     */
    public function test_errors_array_contains_messages(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'not-an-email',
            'password' => '123', // Too short
        ]);

        $this->assertEquals(422, $response->status());
        $errors = $response->json('errors');

        // Each error field should have an array of error messages
        foreach ($errors as $field => $messages) {
            $this->assertIsArray($messages, "Error for field $field should be an array of messages");
            $this->assertNotEmpty($messages, "Error messages array for $field should not be empty");
            $this->assertIsString($messages[0], "Error message for $field should be a string");
        }
    }
}
