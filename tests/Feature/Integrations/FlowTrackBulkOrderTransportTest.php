<?php

namespace Tests\Feature\Integrations;

use Tests\TestCase;

class FlowTrackBulkOrderTransportTest extends TestCase
{
    public function test_reverse_bulk_quote_endpoint_rejects_requests_without_private_bearer_token(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'unit-test-token');

        $this->postJson('/api/integrations/flowtrack/bulk-orders', [
            'flowtrack_inquiry_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_reverse_bulk_quote_endpoint_validates_source_payload_before_any_write(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'unit-test-token');

        $this->withToken('unit-test-token')
            ->postJson('/api/integrations/flowtrack/bulk-orders', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source', 'flowtrack_inquiry_id', 'email']);
    }
}
