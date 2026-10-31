<?php

namespace Tests\Feature\Storefront;

use App\Support\BulkQuoteIcons;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutRemoteAndBulkIconsTest extends TestCase
{
    public function test_remote_area_notice_only_appears_on_confirmed_match(): void
    {
        $shipping = file_get_contents(resource_path('views/storefront/checkout/shipping-address.blade.php'));
        $this->assertStringContainsString('x-show="remote"', $shipping);
        $this->assertStringContainsString("route('checkout.remote-area-status')", $shipping);
        $this->assertStringNotContainsString('An additional charge may apply based on your delivery address.', $shipping);
        $this->assertTrue(Route::has('checkout.remote-area-status'));
    }

    public function test_stripe_checkout_has_handoff_and_failure_recovery(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Storefront/Checkout/CheckoutController.php'));
        $this->assertStringContainsString("->isAvailable('stripe')", $controller);
        $this->assertStringContainsString('redirect()->away((string) $handoff->url)', $controller);
        $this->assertStringContainsString('checkout_payment_error', $controller);
        $this->assertStringNotContainsString('card_number', file_get_contents(resource_path('views/storefront/checkout/review.blade.php')));
    }

    public function test_bulk_quote_icons_use_file_config_and_default_to_builtin_images(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->assertCount(5, BulkQuoteIcons::SLOTS);
        $this->assertNull(BulkQuoteIcons::url('product'));
        $this->assertTrue(Route::has('admin.bulk-quote-icons.edit'));
        $this->assertTrue(Route::has('admin.bulk-quote-icons.update'));
        $section = file_get_contents(resource_path('views/components/storefront/bulk-quote/section.blade.php'));
        $this->assertStringContainsString('bulk-quote-uploaded-icon', $section);
        $this->assertStringContainsString('x-storefront.bulk-quote.icon', $section);
    }
}
