<?php

namespace Tests\Unit\Services\Catalog;

use App\Http\Requests\Admin\ShippingDeliveryPageRequest;
use App\Services\Catalog\ShippingDeliveryPageMediaService;
use App\Services\Storefront\ShippingDeliveryPageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShippingDeliveryPageMediaServiceTest extends TestCase
{
    public function test_all_seven_upload_slots_use_feature_owned_directories(): void
    {
        Storage::fake('public');
        $files = [
            'info_card_icon_0' => UploadedFile::fake()->image('a.png'),
            'info_card_icon_1' => UploadedFile::fake()->image('b.png'),
            'notice_icon' => UploadedFile::fake()->image('notice.png'),
            'checklist_icon_0' => UploadedFile::fake()->image('c0.png'),
            'checklist_icon_1' => UploadedFile::fake()->image('c1.png'),
            'checklist_icon_2' => UploadedFile::fake()->image('c2.png'),
            'checklist_icon_3' => UploadedFile::fake()->image('c3.png'),
        ];
        $mutation = app(ShippingDeliveryPageMediaService::class)->prepare(
            ShippingDeliveryPageRequest::create('/admin/shipping-delivery-page', 'PUT', [], [], $files),
            app(ShippingDeliveryPageService::class)->defaults(),
            $this->payload()
        );

        $this->assertCount(7, $mutation['new_paths']);
        $this->assertStringStartsWith('shipping-delivery-page/info-cards/icons/', $mutation['payload']['info_cards']['cards'][0]['icon_path']);
        $this->assertStringStartsWith('shipping-delivery-page/notice/icons/', $mutation['payload']['notice']['icon_path']);
        $this->assertStringStartsWith('shipping-delivery-page/address-checklist/icons/', $mutation['payload']['address_checklist']['items'][3]['icon_path']);
    }

    public function test_replace_remove_rollback_and_cleanup_only_touch_owned_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('shipping-delivery-page/notice/icons/old.png', 'old');
        Storage::disk('public')->put('branding/logo.png', 'brand');
        $current = app(ShippingDeliveryPageService::class)->defaults();
        $current['notice']['icon_path'] = 'shipping-delivery-page/notice/icons/old.png';
        $request = ShippingDeliveryPageRequest::create('/admin/shipping-delivery-page', 'PUT', ['remove_notice_icon' => '1']);
        $mutation = app(ShippingDeliveryPageMediaService::class)->prepare($request, $current, $this->payload());

        $this->assertNull($mutation['payload']['notice']['icon_path']);
        $this->assertContains('shipping-delivery-page/notice/icons/old.png', $mutation['old_paths']);
        app(ShippingDeliveryPageMediaService::class)->commitCleanup($mutation + ['delete_after_commit' => array_merge($mutation['old_paths'], ['branding/logo.png'])]);
        Storage::disk('public')->assertMissing('shipping-delivery-page/notice/icons/old.png');
        Storage::disk('public')->assertExists('branding/logo.png');
    }


    public function test_rollback_deletes_newly_staged_files_and_preserves_previous_active_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('shipping-delivery-page/notice/icons/current.png', 'current');
        $current = app(ShippingDeliveryPageService::class)->defaults();
        $current['notice']['icon_path'] = 'shipping-delivery-page/notice/icons/current.png';

        $mutation = app(ShippingDeliveryPageMediaService::class)->prepare(
            ShippingDeliveryPageRequest::create(
                '/admin/shipping-delivery-page',
                'PUT',
                [],
                [],
                ['notice_icon' => UploadedFile::fake()->image('replacement.png')]
            ),
            $current,
            $this->payload()
        );
        $newPath = $mutation['payload']['notice']['icon_path'];
        Storage::disk('public')->assertExists($newPath);

        app(ShippingDeliveryPageMediaService::class)->rollback($mutation);

        Storage::disk('public')->assertMissing($newPath);
        Storage::disk('public')->assertExists('shipping-delivery-page/notice/icons/current.png');
    }

    public function test_foreign_paths_are_never_delete_candidates(): void
    {
        $service = app(ShippingDeliveryPageMediaService::class);
        $this->assertTrue($service->isShippingDeliveryOwnedPath('shipping-delivery-page/notice/icons/a.png'));
        $this->assertFalse($service->isShippingDeliveryOwnedPath('about-page/help/icons/a.png'));
        $this->assertFalse($service->isShippingDeliveryOwnedPath('shipping-delivery-page/../branding/logo.png'));
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
