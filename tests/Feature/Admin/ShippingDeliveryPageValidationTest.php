<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\ShippingDeliveryPageRequest;
use App\Services\Storefront\ShippingDeliveryPageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ShippingDeliveryPageValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::put('/__tests/shipping-delivery-page-request', fn (ShippingDeliveryPageRequest $request) => response()->json($request->validatedContent()));
    }

    public function test_valid_fixed_payload_and_safe_destinations_are_accepted(): void
    {
        $payload = $this->payload();
        $payload['cta']['primary_url'] = '/track-order?ref=delivery';
        $payload['cta']['policy_url'] = 'https://example.com/policy';
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertOk()->assertJsonPath('tabs.3.id', 'delivery');
    }

    public function test_reordered_duplicate_or_missing_fixed_slots_are_rejected(): void
    {
        $payload = $this->payload();
        [$payload['tabs'][0], $payload['tabs'][1]] = [$payload['tabs'][1], $payload['tabs'][0]];
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('tabs');

        $payload = $this->payload();
        $payload['faqs']['items'][1]['id'] = $payload['faqs']['items'][0]['id'];
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('faqs.items');

        $payload = $this->payload();
        array_pop($payload['address_checklist']['items']);
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('address_checklist.items');
    }

    public function test_unsafe_destinations_are_rejected(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,bad', '//evil.example', 'not a url', ''] as $url) {
            $payload = $this->payload();
            $payload['cta']['primary_url'] = $url;
            $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('cta.primary_url');
        }
    }

    public function test_icon_upload_validation_accepts_images_and_rejects_non_images_and_oversized_files(): void
    {
        $payload = $this->payload();
        $payload['notice_icon'] = UploadedFile::fake()->image('notice.png', 60, 60);
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertOk();

        $payload = $this->payload();
        $payload['info_card_icon_0'] = UploadedFile::fake()->create('bad.txt', 10, 'text/plain');
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('info_card_icon_0');

        $payload = $this->payload();
        $payload['checklist_icon_3'] = UploadedFile::fake()->image('huge.png')->size(2049);
        $this->put('/__tests/shipping-delivery-page-request', $payload)->assertSessionHasErrors('checklist_icon_3');
    }

    private function payload(): array
    {
        $payload = app(ShippingDeliveryPageService::class)->defaults();
        foreach ($payload['info_cards']['cards'] as &$card) { unset($card['icon_path'], $card['fallback_icon']); }
        unset($card);
        unset($payload['notice']['icon_path'], $payload['notice']['fallback_icon']);
        foreach ($payload['address_checklist']['items'] as &$item) { unset($item['icon_path'], $item['fallback_icon']); }
        unset($item);
        return $payload;
    }
}
