<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\BulkQuoteRequest;
use App\Models\CountryCallingCode;
use App\Services\BulkQuote\QuoteProductInformation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BulkQuoteProductInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_quote_contains_optional_picker_without_querying_product_list(): void
    {
        $this->get(route('quote.request'))->assertOk()
            ->assertSee('Product &amp; Customisation', false)
            ->assertSee('Search by product name or SKU')
            ->assertSee('Optional');
    }

    public function test_prototype_search_is_rendered_once_above_product_type_and_local_captcha_is_absent(): void
    {
        $originalEnvironment = app()->environment();

        try {
            app()->instance('env', 'local');
            $response = $this->get(route('quote.request'))->assertOk()
                ->assertSee('Search for a product')
                ->assertSee('Search by product name or SKU')
                ->assertSee('data-prototype-product-search', false)
                ->assertSee('id="bulk-quote-product-picker"', false)
                ->assertSee('County / State (optional)')
                ->assertDontSee('Optional in local development')
                ->assertDontSee('class="g-recaptcha"', false)
                ->assertDontSee('https://www.google.com/recaptcha/api.js');

            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, 'id="bulk-quote-product-search"'));
            $this->assertLessThan(
                strpos($html, 'id="bulk-product-type"'),
                strpos($html, 'id="bulk-quote-product-search"'),
                'The optional product search must be shown before the general product type input.'
            );
        } finally {
            app()->instance('env', $originalEnvironment);
        }
    }

    public function test_only_published_products_are_searchable_and_product_links_preselect(): void
    {
        $published = $this->product('Custom Team Jersey', 'NJ-001');
        $draft = $this->product('Hidden Team Jersey', 'NJ-002', false);

        $this->getJson(route('quote.products.search', ['q' => 'Jersey']))
            ->assertOk()->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $published->id);
        $this->getJson(route('quote.products.show', $draft->id))->assertNotFound();
        $this->get(route('quote.request', ['product' => $published->slug]))
            ->assertOk()->assertSee('Custom Team Jersey');
    }

    public function test_selection_options_and_size_quantities_are_saved_as_readable_labels(): void
    {
        $product = $this->product('Custom Team Jersey', 'NJ-001');
        $fabric = $product->optionGroups()->create([
            'name' => 'Fabric', 'code' => 'fabric', 'type' => 'select', 'is_active' => true,
        ]);
        $polyester = $fabric->values()->create([
            'label' => 'Performance Polyester', 'code' => 'performance', 'is_default' => true, 'is_active' => true,
        ]);
        $hidden = $fabric->values()->create([
            'label' => 'Inactive Fabric', 'code' => 'inactive', 'is_active' => false,
        ]);
        $group = $product->sizeGroups()->create(['name' => 'Adult', 'code' => 'adult', 'is_active' => true]);
        $medium = $group->sizes()->create(['label' => 'M', 'code' => 'm', 'is_active' => true]);

        $this->getJson(route('quote.products.show', $product->id))
            ->assertOk()->assertJsonPath('product.groups.0.default', (string) $polyester->id)
            ->assertJsonPath('product.size_groups.0.sizes.0.label', 'M')
            ->assertDontSee('Inactive Fabric');

        $data = app(QuoteProductInformation::class)->validateAndSnapshot([
            'product_id' => $product->id,
            'options' => [(string) $fabric->id => (string) $polyester->id],
            'sizes' => [(string) $group->id => [(string) $medium->id => 24]],
            'notes' => 'Place logo on left chest.',
        ]);
        $this->assertSame('Custom Team Jersey', $data['product_name']);
        $this->assertSame('Performance Polyester', $data['options'][0]['value']);
        $this->assertSame(24, $data['size_groups'][0]['sizes'][0]['quantity']);
        $this->assertSame('Place logo on left chest.', $data['notes']);

        $this->expectException(ValidationException::class);
        app(QuoteProductInformation::class)->validateAndSnapshot([
            'product_id' => $product->id,
            'options' => [(string) $fabric->id => (string) $hidden->id],
        ]);
    }

    public function test_product_selection_is_entirely_optional_and_rejects_stale_product(): void
    {
        $product = $this->product('Custom Team Jersey', 'NJ-001', false);
        $this->assertNull(app(QuoteProductInformation::class)->find($product->id));
        $this->expectException(ValidationException::class);
        app(QuoteProductInformation::class)->validateAndSnapshot(['product_id' => $product->id]);
    }

    public function test_selected_product_is_persisted_with_the_existing_quote_flow(): void
    {
        $product = $this->product('Custom Team Jersey', 'NJ-001');
        $fabric = $product->optionGroups()->create(['name' => 'Fabric', 'code' => 'fabric', 'type' => 'select']);
        $choice = $fabric->values()->create(['label' => 'Standard Polyester', 'code' => 'standard']);
        $callingCode = CountryCallingCode::query()->firstOrCreate(
            ['iso_code' => 'US'],
            ['country_name' => 'United States', 'dial_code' => '+1', 'is_active' => true],
        );
        config()->set('transactional_email.enabled', false);
        config()->set('flowtrack.enabled', false);
        $environment = app()->environment();
        try {
            // Switching to local inside a feature test enables CSRF verification.
            // Skip it only for this simulated request; real application CSRF stays enabled.
            $this->withoutMiddleware(ValidateCsrfToken::class);
            app()->instance('env', 'local');
            $this->post(route('quote.request.store'), [
                'full_name' => 'Test Client', 'organization' => 'Junior Team',
                'email' => 'client@example.test', 'phone_country_code_id' => $callingCode->id,
                'phone' => '1234567890', 'product_type' => 'Custom Team Jersey',
                'estimated_quantity' => '10-49', 'sizes_needed' => 'M, L',
                'artwork_details' => 'Please use our team colors.',
                'shipping_address' => '123 Sports Avenue, Boston', 'country' => 'United States',
                'needed_by' => now()->addDays(30)->toDateString(),
                'product_information' => [
                    'product_id' => $product->id,
                    'options' => [(string) $fabric->id => (string) $choice->id],
                    'notes' => 'White trim around collar',
                ],
            ])->assertRedirect();
        } finally {
            app()->instance('env', $environment);
        }
        $quote = BulkQuoteRequest::query()->latest('id')->firstOrFail();
        $this->assertSame('Custom Team Jersey', $quote->product_information['product_name']);
        $this->assertSame('Standard Polyester', $quote->product_information['options'][0]['value']);
        $this->assertSame('White trim around collar', $quote->product_information['notes']);
    }

    private function product(string $name, string $sku, bool $published = true): Product
    {
        return Product::query()->create([
            'name' => $name,
            'sku' => $sku,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.strtolower($sku),
            'status' => $published ? 'active' : 'draft',
            'is_active' => true,
        ]);
    }
}
