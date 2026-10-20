<?php

namespace Tests\Feature\Integrations;

use App\Services\Order\OrderExperienceService;
use Tests\TestCase;

class FlowTrackOrderProgressDisplayTest extends TestCase
{
    public function test_paid_order_uses_flowtracker_production_phase_for_timeline(): void
    {
        $steps = app(OrderExperienceService::class)->trackingTimeline([
            'status' => 'design_review',
            'payment_status' => 'paid',
            'flowtrack_operational_stage' => 'Production',
            'flowtrack_operational_status' => 'in_progress',
            'flowtrack_operational_progress' => 45,
        ]);

        $this->assertSame('done', $steps[1]['state']);
        $this->assertSame('current', $steps[4]['state']);
        $this->assertStringContainsString('45% complete', $steps[4]['description']);
    }

    public function test_unpaid_order_does_not_automatically_mark_payment_as_done(): void
    {
        $steps = app(OrderExperienceService::class)->trackingTimeline([
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'flowtrack_operational_stage' => 'Production',
            'flowtrack_operational_status' => 'in_progress',
            'flowtrack_operational_progress' => 45,
        ]);

        $this->assertSame('current', $steps[1]['state']);
        $this->assertSame('pending', $steps[4]['state']);
    }
}
