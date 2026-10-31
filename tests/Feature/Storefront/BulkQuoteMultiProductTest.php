<?php

namespace Tests\Feature\Storefront;

use App\Models\BulkQuoteRequest;
use App\Models\CountryCallingCode;
use App\Models\Product;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkQuoteMultiProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_requested_products_use_existing_quote_record_without_extra_tables(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config()->set('transactional_email.enabled', false);
        config()->set('flowtrack.enabled', false);
        $oldEnvironment = app()->environment();
        app()->instance('env', 'local');

        try {
            $code = CountryCallingCode::query()->firstOrCreate(
                ['iso_code' => 'GB'],
                ['country_name' => 'United Kingdom', 'dial_code' => '+44', 'is_active' => true]
            );
            $catalogue = Product::query()->create([
                'name' => 'Custom Team Jersey', 'sku' => 'NPQ-001',
                'slug' => 'custom-team-jersey-quote-test', 'status' => 'active', 'is_active' => true,
            ]);
            $response = $this->post(route('quote.request.store'), [
                'quote_products_json' => json_encode([
                    [
                        'mode' => 'existing', 'name' => $catalogue->name,
                        'catalog_product_id' => $catalogue->id,
                        'quantity' => '120', 'sizes' => 'S, M, L', 'material' => '150 GSM polyester', 'colors' => 'Navy', 'description' => '',
                        'customizations' => ['logo'],
                    ],
                    [
                        'mode' => 'new', 'name' => 'Custom Sports Towel',
                        'description' => 'Embroidered cotton sports towel.',
                        'quantity' => '200', 'sizes' => '40 x 80 cm',
                        'material' => 'Cotton', 'colors' => 'White and navy',
                        'customizations' => ['embroidery'],
                    ],
                ], JSON_THROW_ON_ERROR),
                'full_name' => 'Test Client', 'organization' => 'Test Club',
                'email' => 'club@example.test', 'phone_country_code_id' => $code->id,
                'phone' => '7700900123', 'shipping_address' => 'London Sports Centre, London',
                'country' => 'United Kingdom', 'needed_by' => now()->addDays(20)->toDateString(),
            ]);
            $response->assertSessionHasNoErrors()->assertRedirect();
            $quote = BulkQuoteRequest::query()->latest('id')->firstOrFail();
            $items = $quote->product_information['selected_products'];
            $this->assertCount(2, $items);
            $this->assertSame(120, $items[0]['quantity']);
            $this->assertSame($catalogue->id, $items[0]['product_id']);
            // Fabric and colour are not configured on this catalogue product,
            // so free-text client values must not become invented options.
            $this->assertSame('', $items[0]['material']);
            $this->assertSame('', $items[0]['colors']);
            $this->assertSame('', $items[0]['sizes']);
            $this->assertSame('Custom Sports Towel', $items[1]['name']);
            $this->assertSame('Cotton', $items[1]['material']);
            $this->assertSame('100-499', $quote->estimated_quantity);
        } finally {
            app()->instance('env', $oldEnvironment);
        }
    }

    public function test_builder_requires_material_colours_size_and_quantity(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $environment = app()->environment();
        app()->instance('env', 'local');
        try {
            $this->post(route('quote.request.store'), [
                'quote_products_json' => json_encode([[
                    'mode' => 'new', 'name' => 'Training Bag',
                    'description' => 'A custom sport training bag',
                    'quantity' => '0', 'material' => '', 'colors' => '', 'sizes' => '',
                ]], JSON_THROW_ON_ERROR),
            ])->assertSessionHasErrors([
                'quote_products.0.material', 'quote_products.0.colors',
                'quote_products.0.sizes', 'quote_products.0.quantity',
            ]);
        } finally {
            app()->instance('env', $environment);
        }
    }

    public function test_builder_rejects_empty_product_request(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $environment = app()->environment();
        app()->instance('env', 'local');
        try {
            $this->post(route('quote.request.store'), ['quote_products_json' => '[]'])
                ->assertSessionHasErrors(['quote_products']);
        } finally {
            app()->instance('env', $environment);
        }
    }
}
