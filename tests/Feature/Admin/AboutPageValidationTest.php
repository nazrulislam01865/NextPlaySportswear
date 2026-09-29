<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\AboutPageRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AboutPageValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::put('/__tests/about-page-request', function (AboutPageRequest $request) {
            return response()->json($request->validatedContent());
        });
    }

    public function test_about_request_accepts_all_valid_text_fields_and_safe_relative_and_https_urls(): void
    {
        $payload = $this->validPayload();
        $payload['cta']['primary_url'] = '/products?sort=newest';
        $payload['cta']['secondary_url'] = 'https://example.com/bulk';
        $payload['help']['button_url'] = 'http://example.com/help';

        $this->put('/__tests/about-page-request', $payload)
            ->assertOk()
            ->assertJsonPath('hero.title', 'ABOUT NEXTPLAY')
            ->assertJsonPath('what_we_do.cards.0.id', 'custom-teamwear')
            ->assertJsonPath('how_we_work.steps.3.id', 'place-order');
    }

    public function test_about_request_rejects_javascript_data_protocol_relative_and_malformed_urls(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,hi', '//evil.example/path', 'https://', 'not a url'] as $badUrl) {
            $payload = $this->validPayload();
            $payload['cta']['primary_url'] = $badUrl;

            $this->put('/__tests/about-page-request', $payload)
                ->assertSessionHasErrors('cta.primary_url');
        }
    }

    public function test_about_request_rejects_missing_or_extra_service_process_and_gallery_slots(): void
    {
        $payload = $this->validPayload();
        array_pop($payload['what_we_do']['cards']);
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('what_we_do.cards');

        $payload = $this->validPayload();
        $payload['how_we_work']['steps'][] = $payload['how_we_work']['steps'][0];
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('how_we_work.steps');

        $payload = $this->validPayload();
        array_pop($payload['gallery']['items']);
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('gallery.items');
    }

    public function test_about_request_rejects_changed_duplicate_or_unknown_stable_slot_ids(): void
    {
        $payload = $this->validPayload();
        $payload['what_we_do']['cards'][1]['id'] = 'custom-teamwear';
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('what_we_do.cards');

        $payload = $this->validPayload();
        $payload['how_we_work']['steps'][0]['id'] = 'unknown';
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('how_we_work.steps');

        $payload = $this->validPayload();
        $payload['gallery']['items'][2]['id'] = 'celebration';
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('gallery.items');
    }

    public function test_about_request_rejects_invalid_image_and_icon_types_and_enforces_size_limits(): void
    {
        $payload = $this->validPayload();
        $payload['introduction_image'] = UploadedFile::fake()->create('intro.txt', 10, 'text/plain');
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('introduction_image');

        $payload = $this->validPayload();
        $payload['service_icon_0'] = UploadedFile::fake()->create('icon.png', 2049, 'image/png');
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('service_icon_0');

        $payload = $this->validPayload();
        $payload['gallery_image_0'] = UploadedFile::fake()->create('gallery.jpg', 10241, 'image/jpeg');
        $this->put('/__tests/about-page-request', $payload)->assertSessionHasErrors('gallery_image_0');
    }

    public function test_about_request_requires_alt_text_when_a_custom_upload_is_submitted(): void
    {
        $payload = $this->validPayload();
        $payload['what_we_do']['cards'][0]['icon_alt'] = '';
        $payload['service_icon_0'] = UploadedFile::fake()->image('icon.png', 128, 128);

        $this->put('/__tests/about-page-request', $payload)
            ->assertSessionHasErrors('what_we_do.cards.0.icon_alt');
    }

    private function validPayload(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'NEXTPLAY SPORTSWEAR',
                'title' => 'ABOUT NEXTPLAY',
                'description' => 'Sportswear for teams, clubs and people who love to play.',
            ],
            'introduction' => [
                'title' => 'We help teams bring their ideas to life through custom sportswear and everyday performance gear.',
                'description' => 'From your first design choice to the final order, our aim is to make the process clear and easy to follow.',
                'image_alt' => 'Team image',
            ],
            'what_we_do' => [
                'title' => 'WHAT WE DO',
                'cards' => [
                    ['id' => 'custom-teamwear', 'title' => 'Custom Teamwear', 'description' => 'Custom teamwear description.', 'icon_alt' => 'Teamwear icon'],
                    ['id' => 'sportswear-gear', 'title' => 'Sportswear & Gear', 'description' => 'Sportswear description.', 'icon_alt' => 'Sportswear icon'],
                    ['id' => 'bulk-orders', 'title' => 'Bulk Orders', 'description' => 'Bulk order description.', 'icon_alt' => 'Bulk icon'],
                ],
            ],
            'how_we_work' => [
                'title' => 'HOW WE WORK',
                'steps' => [
                    ['id' => 'choose-product', 'number' => '1', 'title' => 'Choose a product', 'description' => 'Choose.', 'icon_alt' => 'Choose icon'],
                    ['id' => 'personalise', 'number' => '2', 'title' => 'Personalise it', 'description' => 'Personalise.', 'icon_alt' => 'Personalise icon'],
                    ['id' => 'review-details', 'number' => '3', 'title' => 'Review your details', 'description' => 'Review.', 'icon_alt' => 'Review icon'],
                    ['id' => 'place-order', 'number' => '4', 'title' => 'Place your order', 'description' => 'Order.', 'icon_alt' => 'Order icon'],
                ],
            ],
            'gallery' => [
                'items' => [
                    ['id' => 'team', 'image_alt' => 'Team gallery'],
                    ['id' => 'fabric', 'image_alt' => 'Fabric gallery'],
                    ['id' => 'number', 'image_alt' => 'Number gallery'],
                    ['id' => 'celebration', 'image_alt' => 'Celebration gallery'],
                ],
            ],
            'cta' => [
                'eyebrow' => 'MADE FOR YOUR TEAM',
                'title' => 'Explore custom options for your club, event or organisation.',
                'primary_label' => 'EXPLORE PRODUCTS',
                'primary_url' => '/products',
                'secondary_label' => 'REQUEST A BULK QUOTE',
                'secondary_url' => '/bulk-quote',
            ],
            'help' => [
                'title' => 'NEED HELP?',
                'description' => 'Our team is here to help.',
                'icon_alt' => 'Support icon',
                'button_label' => 'Contact Us',
                'button_url' => '/contact-us',
            ],
            'seo' => [
                'title' => 'About NextPlay',
                'description' => 'About NextPlay description.',
            ],
        ];
    }
}
