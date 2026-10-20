<?php

namespace Tests\Feature\Integrations;

use Tests\TestCase;

final class FlowTrackCatalogAuthenticationTest extends TestCase
{
    public function test_catalog_and_reverse_order_api_reject_missing_or_wrong_bearer_token(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'unit-test-shared-key');

        $this->getJson('/api/integrations/flowtrack/catalog/products')->assertUnauthorized();
        $this->getJson('/api/integrations/flowtrack/catalog/customers', [
            'Authorization' => 'Bearer wrong-key',
        ])->assertUnauthorized();
        $this->postJson('/api/integrations/flowtrack/orders/from-flowtrack', [
            'flow_job_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_integration_rejects_requests_when_disabled(): void
    {
        config()->set('flowtrack.enabled', false);
        config()->set('flowtrack.token', 'unit-test-shared-key');
        $this->getJson('/api/integrations/flowtrack/catalog/products', [
            'Authorization' => 'Bearer unit-test-shared-key',
        ])->assertStatus(503);
    }
}
