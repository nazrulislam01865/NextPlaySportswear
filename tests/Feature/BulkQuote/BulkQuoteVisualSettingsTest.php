<?php

namespace Tests\Feature\BulkQuote;

use App\Models\Product;
use App\Services\BulkQuote\QuoteProductInformation;
use App\Support\BulkQuoteContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkQuoteVisualSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_preexisting_option_image_is_included_without_changing_option_ids(): void
    {
        $product = Product::query()->create([
            'name' => 'Test training jersey', 'sku' => 'BQV-100', 'slug' => 'test-training-jersey',
            'status' => 'active', 'is_active' => true,
        ]);
        $group = $product->optionGroups()->create(['name' => 'Fabric', 'code' => 'fabric', 'type' => 'image', 'is_active' => true]);
        $value = $group->values()->create([
            'label' => 'Breathable fabric', 'code' => 'breathable', 'image_url' => 'https://example.test/fabric.png',
            'is_active' => true,
        ]);
        $description = app(QuoteProductInformation::class)->describe(
            app(QuoteProductInformation::class)->find($product->id),
        );
        $this->assertSame((string) $value->id, $description['groups'][0]['values'][0]['id']);
        $this->assertSame('https://example.test/fabric.png', $description['groups'][0]['values'][0]['image']);
    }

    public function test_whatsapp_override_is_stored_without_a_database_table(): void
    {
        Storage::fake('local');
        BulkQuoteContact::save('+44 7700 900123');
        $this->assertSame('+44 7700 900123', BulkQuoteContact::whatsappNumber());
        $this->assertSame('https://wa.me/447700900123', BulkQuoteContact::whatsappUrl());
        $this->assertSame('+44 7700 900123', json_decode(
            Storage::disk('local')->get('nextplay/bulk-quote-contact.json'), true,
        )['whatsapp_number']);
        BulkQuoteContact::save('');
    }
}
