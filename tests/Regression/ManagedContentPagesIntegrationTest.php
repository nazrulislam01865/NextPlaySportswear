<?php

namespace Tests\Regression;

use App\Services\Storefront\ArtworkGuidelinesPageService;
use App\Services\Storefront\ContactPageService;
use App\Services\Storefront\HowToOrderPageService;
use App\Services\Storefront\PrivacyPageService;
use App\Services\Storefront\ReturnsPolicyPageService;
use App\Services\Storefront\TermsPageService;
use Tests\TestCase;

class ManagedContentPagesIntegrationTest extends TestCase
{
    public function test_all_managed_public_pages_render_with_service_defaults(): void
    {
        foreach ([
            'how-to-order' => 'How to Order',
            'artwork-guidelines' => 'Artwork Guidelines',
            'returns' => 'Returns & Refunds',
            'terms' => 'Terms and Conditions',
            'privacy' => 'Privacy Policy',
            'contact' => 'Contact Us',
        ] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title);
        }
    }

    public function test_every_new_editor_has_matching_service_defaults(): void
    {
        foreach ([
            ArtworkGuidelinesPageService::class,
            ContactPageService::class,
            HowToOrderPageService::class,
            PrivacyPageService::class,
            ReturnsPolicyPageService::class,
            TermsPageService::class,
        ] as $service) {
            $this->assertIsArray(app($service)->defaults());
        }
    }
}
