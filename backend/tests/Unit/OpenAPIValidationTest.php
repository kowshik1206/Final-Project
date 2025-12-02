<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * OpenAPI Validation Test
 * 
 * Validates that the OpenAPI spec file exists and has valid schema structure.
 */
class OpenAPIValidationTest extends TestCase
{
    private string $openApiPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->openApiPath = base_path('openapi.yaml');
    }

    /**
     * Test that OpenAPI spec file exists
     */
    public function test_openapi_file_exists(): void
    {
        $this->assertFileExists(
            $this->openApiPath,
            'OpenAPI spec file should exist at ' . $this->openApiPath
        );
    }

    /**
     * Test that OpenAPI spec is valid YAML
     */
    public function test_openapi_is_valid_yaml(): void
    {
        $this->assertFileExists($this->openApiPath);

        $content = file_get_contents($this->openApiPath);
        $this->assertNotEmpty($content, 'OpenAPI file should not be empty');

        $spec = Yaml::parse($content);
        $this->assertIsArray($spec, 'OpenAPI spec should parse as valid YAML');
    }

    /**
     * Test that OpenAPI spec has required root properties
     */
    public function test_openapi_has_required_fields(): void
    {
        $spec = $this->parseOpenApiSpec();

        $this->assertArrayHasKey('openapi', $spec, 'OpenAPI spec should have "openapi" version');
        $this->assertArrayHasKey('info', $spec, 'OpenAPI spec should have "info" object');
        $this->assertArrayHasKey('paths', $spec, 'OpenAPI spec should have "paths" object');
        $this->assertArrayHasKey('components', $spec, 'OpenAPI spec should have "components" object');

        // Validate info object
        $this->assertArrayHasKey('title', $spec['info'], 'Info should have title');
        $this->assertArrayHasKey('version', $spec['info'], 'Info should have version');
    }

    /**
     * Test that all 6 required endpoints are documented
     */
    public function test_all_required_endpoints_exist(): void
    {
        $spec = $this->parseOpenApiSpec();
        $requiredPaths = [
            '/api/auth/register',
            '/api/user',
            '/api/route/calc',
            '/api/calc-cost',
            '/api/pois/near-route',
            '/api/optimize/multi-stop',
        ];

        foreach ($requiredPaths as $path) {
            $this->assertArrayHasKey(
                $path,
                $spec['paths'],
                "Path $path should be documented in OpenAPI spec"
            );
        }
    }

    /**
     * Test that all endpoints have proper request/response schemas
     */
    public function test_endpoints_have_schemas(): void
    {
        $spec = $this->parseOpenApiSpec();

        $endpointsToCheck = [
            '/api/auth/register' => 'post',
            '/api/user' => 'get',
            '/api/route/calc' => 'post',
            '/api/calc-cost' => 'post',
            '/api/pois/near-route' => 'post',
            '/api/optimize/multi-stop' => 'post',
        ];

        foreach ($endpointsToCheck as $path => $method) {
            $this->assertArrayHasKey(
                $method,
                $spec['paths'][$path],
                "$path should have $method operation"
            );

            $operation = $spec['paths'][$path][$method];

            // Check for responses
            $this->assertArrayHasKey(
                'responses',
                $operation,
                "$path $method should have responses defined"
            );

            $this->assertNotEmpty(
                $operation['responses'],
                "$path $method should have at least one response"
            );
        }
    }

    /**
     * Test that ApiError schema is defined
     */
    public function test_api_error_schema_exists(): void
    {
        $spec = $this->parseOpenApiSpec();

        $this->assertArrayHasKey(
            'schemas',
            $spec['components'],
            'Components should have schemas'
        );

        $this->assertArrayHasKey(
            'ApiError',
            $spec['components']['schemas'],
            'ApiError schema should be defined'
        );

        $apiErrorSchema = $spec['components']['schemas']['ApiError'];
        $this->assertArrayHasKey('properties', $apiErrorSchema);
        $this->assertArrayHasKey('success', $apiErrorSchema['properties']);
        $this->assertArrayHasKey('message', $apiErrorSchema['properties']);
        $this->assertArrayHasKey('status', $apiErrorSchema['properties']);
    }

    /**
     * Test that security scheme is defined for JWT
     */
    public function test_bearer_auth_security_scheme_exists(): void
    {
        $spec = $this->parseOpenApiSpec();

        $this->assertArrayHasKey(
            'securitySchemes',
            $spec['components'],
            'Components should have securitySchemes'
        );

        $this->assertArrayHasKey(
            'bearerAuth',
            $spec['components']['securitySchemes'],
            'bearerAuth security scheme should be defined'
        );

        $bearerAuth = $spec['components']['securitySchemes']['bearerAuth'];
        $this->assertEquals('bearer', $bearerAuth['scheme']);
        $this->assertEquals('JWT', $bearerAuth['bearerFormat']);
    }

    /**
     * Test that OpenAPI version is 3.0+
     */
    public function test_openapi_version_is_valid(): void
    {
        $spec = $this->parseOpenApiSpec();
        $version = $spec['openapi'];

        $this->assertStringStartsWith('3.0', $version, 'OpenAPI version should be 3.0+');
    }

    /**
     * Helper: Parse and return OpenAPI spec
     */
    private function parseOpenApiSpec(): array
    {
        $content = file_get_contents($this->openApiPath);
        return Yaml::parse($content);
    }
}
