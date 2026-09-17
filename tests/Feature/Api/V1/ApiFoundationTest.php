<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    public function test_health_endpoint_uses_versioned_contract_and_request_id(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure([
                'data' => ['status', 'api_version', 'timestamp'],
                'meta',
                'request_id',
            ]);
    }

    public function test_valid_incoming_request_id_is_preserved(): void
    {
        $response = $this->withHeader('X-Request-ID', 'np-test-123')
            ->getJson('/api/v1/health');

        $response->assertHeader('X-Request-ID', 'np-test-123')
            ->assertJsonPath('request_id', 'np-test-123');
    }

    public function test_invalid_incoming_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-ID', "bad request id with spaces")
            ->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertNotSame("bad request id with spaces", $response->headers->get('X-Request-ID'));
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9._:-]{1,100}$/',
            (string) $response->headers->get('X-Request-ID')
        );
    }

    public function test_v1_not_found_errors_use_the_common_contract(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('message', 'Resource not found.')
            ->assertJsonStructure(['message', 'request_id']);
    }

    public function test_v1_method_not_allowed_errors_use_the_common_contract(): void
    {
        $this->postJson('/api/v1/health')
            ->assertStatus(405)
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('message', 'Method not allowed.')
            ->assertJsonStructure(['message', 'request_id']);
    }

    public function test_v1_validation_errors_use_the_common_contract(): void
    {
        Route::post('/api/v1/testing/validation', function (Request $request) {
            $request->validate(['name' => ['required', 'string']]);

            return response()->json(['ok' => true]);
        });

        $this->postJson('/api/v1/testing/validation', [])
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure([
                'message',
                'errors' => ['name'],
                'request_id',
            ]);
    }

    public function test_v1_server_errors_do_not_leak_exception_details(): void
    {
        Route::get('/api/v1/testing/server-error', function (): never {
            throw new \RuntimeException('SQL select secret_token from integrations');
        });

        $response = $this->getJson('/api/v1/testing/server-error')
            ->assertStatus(500)
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('message', 'Server error.')
            ->assertJsonStructure(['message', 'request_id']);

        $this->assertStringNotContainsString('secret_token', $response->getContent());
        $this->assertStringNotContainsString('integrations', $response->getContent());
    }

    public function test_existing_flowtrack_routes_are_not_moved_under_v1(): void
    {
        $this->assertTrue(Route::has('api.flowtrack.order-items.artwork'));
        $this->assertTrue(Route::has('api.flowtrack.bulk-quotes.attachment'));

        $this->assertStringStartsWith(
            'api/integrations/flowtrack/',
            Route::getRoutes()->getByName('api.flowtrack.order-items.artwork')->uri()
        );
        $this->assertStringStartsWith(
            'api/integrations/flowtrack/',
            Route::getRoutes()->getByName('api.flowtrack.bulk-quotes.attachment')->uri()
        );
    }
}
