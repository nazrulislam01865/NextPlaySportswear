<?php

namespace Tests\Feature\Admin;

use App\Models\BulkQuoteRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkQuote\BulkQuoteQuotationEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BulkQuoteQuotationPrototypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('transactional_email.enabled', false);
        config()->set('transactional_email.recipients.sales', '');
    }

    public function test_quotation_tab_renders_prototype_blocks_product_lines_options_and_draft_button(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']))
            ->assertOk()
            ->assertSee('Prepare bulk quotation')
            ->assertSee('BULK ORDER')
            ->assertSee('SAMPLE')
            ->assertSee('DOCUMENTS &amp; SEND', false)
            ->assertSee('Skull cap')
            ->assertSee('Heat transfer')
            ->assertSee('name="lines[0][quantity]"', false)
            ->assertSee('name="lines[0][options]['.$group->id.']"', false)
            ->assertSee('Save draft')
            ->assertSee('Preview quotation')
            ->assertSee('Submit &amp; send quotation', false);
    }

    public function test_pricing_uses_quantities_configured_option_surcharges_and_separate_sample_shipping(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $result = app(BulkQuoteQuotationEditor::class)->calculate($quote, $this->pricing($group->id, $option->id, [
            'sample_enabled' => '1',
        ]));

        // Bulk: 4*5.50 + 4*0.50 option + 4*0.25 imprint + 2 setup + 10 shipping + 3 private = 40.
        // Printed sample: 1*7 + 1*0.50 option + 1*0.30 imprint + 1 setup + 2 shipping = 10.80.
        $this->assertSame(4, $result['quantity']);
        $this->assertSame(29.0, $result['subtotal']);
        $this->assertSame(6.8, $result['customization']);
        $this->assertSame(15.0, $result['shipping']);
        $this->assertSame(50.8, $result['pricing_breakdown']['grand_total']);
        $this->assertSame(10.8, $result['sample']['total']);
    }

    public function test_remote_area_charge_is_applied_once_per_shipment_not_per_item(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $quote->forceFill(['is_remote_area' => true, 'postal_code' => null])->save();
        $result = app(BulkQuoteQuotationEditor::class)->calculate($quote, $this->pricing($group->id, $option->id, [
            'lines' => [array_merge($this->pricing($group->id, $option->id)['lines'][0], ['quantity' => '100'])],
            'remote_charge' => '12',
        ]));
        $this->assertSame(12.0, $result['pricing_breakdown']['remote_area_per_shipment']);
        $this->assertSame(100, $result['quantity']);
        $this->assertSame(550.0, $result['subtotal']);
        $this->assertSame(77.0, $result['customization']);
        $this->assertSame(25.0, $result['shipping']);
    }

    public function test_product_option_ids_must_belong_to_the_selected_catalog_product(): void
    {
        [$quote, $group] = $this->quoteWithProduct();
        $this->expectException(ValidationException::class);
        app(BulkQuoteQuotationEditor::class)->calculate($quote, $this->pricing($group->id, '999999'));
    }

    public function test_admin_draft_does_not_publish_or_notify_customer(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.bulk-quotes.draft', $quote), $this->pricing($group->id, $option->id, [
                'payment_terms' => 'Deposit before production',
                'valid_until' => now()->addMonth()->toDateString(),
            ]))
            ->assertRedirect(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']));
        $quote->refresh();
        $this->assertSame(0, $quote->quotation_version);
        $this->assertSame('new', $quote->status);
        $this->assertSame('Deposit before production', $quote->quotation_draft['payment_terms']);
        $this->assertSame(4, $quote->quotation_draft['lines'][0]['quantity']);
        $this->assertSame(0, $quote->quoteFiles()->count());
    }

    public function test_publish_calculates_prices_on_server_and_keeps_private_quotation_file_contract(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $payload = $this->pricing($group->id, $option->id, [
            'sample_enabled' => '1',
            'payment_terms' => '50% deposit / balance before dispatch',
            'valid_until' => now()->addMonth()->toDateString(),
            'subtotal' => 1, // deliberately forged browser summary: must NOT be trusted
            'shipping' => 1,
            'files' => [UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf')],
        ]);
        $this->actingAs($admin, 'admin')->post(route('admin.bulk-quotes.publish', $quote), $payload)
            ->assertRedirect(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']))
            ->assertSessionHasNoErrors();
        $quote->refresh();
        $this->assertSame(1, $quote->quotation_version);
        $this->assertSame('50.80', $quote->quoted_amount);
        $this->assertSame(4, $quote->quotation_details['quantity']);
        // JSON preserves monetary values but not their PHP int/float subtype.
        $this->assertEqualsWithDelta(29.0, (float) $quote->quotation_details['subtotal'], 0.001);
        $this->assertEqualsWithDelta(6.8, (float) $quote->quotation_details['customization'], 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $quote->quotation_details['shipping'], 0.001);
        $this->assertNull($quote->quotation_draft);
        $file = $quote->quoteFiles()->where('category', 'quotation')->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($file->path));
    }

    public function test_sample_options_can_be_priced_independently_from_bulk_options(): void
    {
        [$quote, $group, $bulkOption] = $this->quoteWithProduct();
        $other = $group->values()->create([
            'label' => 'Embroidery', 'code' => 'embroidery', 'price_adjustment' => 1.20,
            'charge_type' => 'per_unit', 'is_active' => true,
        ]);
        $input = $this->pricing($group->id, $bulkOption->id, [
            'sample_enabled' => '1',
            'lines' => [['sample_options' => [$group->id => (string) $other->id]]],
        ]);
        $result = app(BulkQuoteQuotationEditor::class)->calculate($quote, $input);
        $this->assertEqualsWithDelta(11.50, $result['sample']['total'], 0.001);
        $this->assertEqualsWithDelta(51.50, $result['pricing_breakdown']['grand_total'], 0.001);
        $this->assertSame((string) $bulkOption->id, $result['pricing_lines'][0]['options'][$group->id]);
        $this->assertSame((string) $other->id, $result['pricing_lines'][0]['sample_options'][$group->id]);
    }

    public function test_required_sample_option_must_be_chosen_for_printed_sample(): void
    {
        [$quote, $group, $bulkOption] = $this->quoteWithProduct();
        $this->expectException(ValidationException::class);
        app(BulkQuoteQuotationEditor::class)->calculate($quote, $this->pricing($group->id, $bulkOption->id, [
            'sample_enabled' => '1',
            'lines' => [['sample_options' => [$group->id => '']]],
        ]));
    }

    public function test_quotation_editor_keeps_request_header_and_has_master_payment_and_validity_dropdowns(): void
    {
        [$quote] = $this->quoteWithProduct();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']))
            ->assertOk()
            ->assertSee('Bulk Quotation Request')
            ->assertSee('Request Details')
            ->assertSee('Activity Log')
            ->assertSee('name="valid_until"', false)
            ->assertSee('1 day')
            ->assertSee('60 days')
            ->assertSee('name="payment_term_id"', false)
            ->assertSee('Full payment (100% before production)')
            ->assertSee('data-option-kind="sample_options"', false);
    }

    public function test_payment_terms_are_resolved_from_active_master_data_not_browser_text(): void
    {
        [$quote, $group, $option] = $this->quoteWithProduct();
        $term = \App\Models\BulkQuotePaymentTerm::query()->active()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $input = array_merge($this->pricing($group->id, $option->id), [
            'payment_term_id' => $term->id,
            'payment_terms' => 'Forged browser text',
            'valid_until' => now()->addDays(7)->toDateString(),
        ]);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.bulk-quotes.draft', $quote), $input)
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertSame($term->label, $quote->fresh()->quotation_draft['payment_terms']);
    }

    public function test_selected_catalog_groups_and_values_render_first_with_editable_dropdowns(): void
    {
        [$quote, $imprint, $imprintChoice] = $this->quoteWithProduct();
        $product = Product::query()->findOrFail($imprint->product_id);
        $fabric = $product->optionGroups()->create([
            'name' => 'Fabric', 'code' => 'fabric', 'type' => 'select',
            'is_active' => true, 'is_required' => true,
        ]);
        $unselected = $fabric->values()->create([
            'label' => 'Standard mesh', 'code' => 'standard-mesh', 'price_adjustment' => 0,
            'charge_type' => 'included', 'is_active' => true,
        ]);
        $chosen = $fabric->values()->create([
            'label' => '180G Jacquard', 'code' => 'jacquard', 'price_adjustment' => 0.20,
            'charge_type' => 'per_unit', 'is_active' => true,
        ]);
        $details = (array) $quote->product_information;
        $details['selected_products'][0]['options'] = [['label' => 'Fabric', 'value' => '180G Jacquard']];
        $quote->forceFill(['product_information' => $details])->save();

        $prepared = app(BulkQuoteQuotationEditor::class)->prepare($quote);
        $this->assertSame('Fabric', $prepared['lines'][0]['groups'][0]['label']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']))
            ->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('name="lines[0][options]['.$fabric->id.']"', $html);
        $this->assertStringContainsString('name="lines[0][options]['.$imprint->id.']"', $html);
        $this->assertStringContainsString('Product option', $html);
        $fabricSelector = 'id="np-quote-options-0-'.$fabric->id.'"';
        $imprintSelector = 'id="np-quote-options-0-'.$imprint->id.'"';
        $this->assertLessThan(strpos($html, $imprintSelector), strpos($html, $fabricSelector));
        $selectSection = substr($html, strpos($html, $fabricSelector));
        $selectSection = substr($selectSection, 0, strpos($selectSection, '</select>'));
        $this->assertLessThan(
            strpos($selectSection, 'value="'.$unselected->id.'"'),
            strpos($selectSection, 'value="'.$chosen->id.'"'),
        );

        $base = $this->pricing($imprint->id, $imprintChoice->id);
        $base['lines'][0]['options'][$fabric->id] = (string) $chosen->id;
        $withFabric = app(BulkQuoteQuotationEditor::class)->calculate($quote, $base);
        $base['lines'][0]['options'][$fabric->id] = (string) $unselected->id;
        $withoutFabric = app(BulkQuoteQuotationEditor::class)->calculate($quote, $base);
        $this->assertEqualsWithDelta(0.80,
            $withFabric['pricing_breakdown']['grand_total'] - $withoutFabric['pricing_breakdown']['grand_total'], 0.001);
    }

    public function test_multiple_checkbox_options_have_selected_first_dropdown_and_configured_charges(): void
    {
        [$quote, $imprint, $imprintChoice] = $this->quoteWithProduct();
        $product = Product::query()->findOrFail($imprint->product_id);
        $group = $product->optionGroups()->create([
            'name' => 'Print', 'code' => 'print', 'type' => 'checkbox',
            'is_active' => true, 'is_required' => false,
        ]);
        $unused = $group->values()->create([
            'label' => 'Screen printing', 'code' => 'screen-print', 'price_adjustment' => 0.25,
            'charge_type' => 'per_unit', 'is_active' => true,
        ]);
        $selected = $group->values()->create([
            'label' => 'Heat press', 'code' => 'heat-press', 'price_adjustment' => 0.18,
            'charge_type' => 'per_unit', 'is_active' => true,
        ]);
        $details = (array) $quote->product_information;
        $details['selected_products'][0]['options'][] = ['label' => 'Print', 'values' => ['Heat press']];
        $quote->forceFill(['product_information' => $details])->save();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.bulk-quotes.show', ['bulkQuote' => $quote, 'tab' => 'messages']))
            ->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('np-quote-multi-dropdown', $html);
        $this->assertStringContainsString('name="lines[0][options]['.$group->id.'][]"', $html);
        $inputSection = substr($html, strpos($html, 'id="np-quote-options-0-'.$group->id.'"'));
        $inputSection = substr($inputSection, 0, strpos($inputSection, '</details>'));
        $this->assertLessThan(
            strpos($inputSection, 'value="'.$unused->id.'"'),
            strpos($inputSection, 'value="'.$selected->id.'"'),
        );
        $base = $this->pricing($imprint->id, $imprintChoice->id);
        $base['lines'][0]['options'][$group->id] = [(string) $selected->id];
        $first = app(BulkQuoteQuotationEditor::class)->calculate($quote, $base);
        $base['lines'][0]['options'][$group->id] = [(string) $selected->id, (string) $unused->id];
        $both = app(BulkQuoteQuotationEditor::class)->calculate($quote, $base);
        $this->assertEqualsWithDelta(1.00,
            $both['pricing_breakdown']['grand_total'] - $first['pricing_breakdown']['grand_total'], 0.001);
    }

    private function quoteWithProduct(): array
    {
        $product = Product::query()->create([
            'name' => 'Skull cap', 'slug' => 'quote-test-skull-cap', 'sku' => 'QUOTE-SK-01',
            'status' => 'active', 'is_active' => true, 'base_price' => 5.50,
        ]);
        $group = $product->optionGroups()->create([
            'name' => 'Imprint method', 'code' => 'imprint', 'type' => 'select',
            'is_active' => true, 'is_required' => true,
        ]);
        $option = $group->values()->create([
            'label' => 'Heat transfer', 'code' => 'heat-transfer', 'price_adjustment' => 0.5,
            'charge_type' => 'per_unit', 'is_active' => true,
        ]);
        $quote = BulkQuoteRequest::query()->create([
            'reference' => 'BQ-QUOTE-DEMO', 'full_name' => 'Joe Galvan',
            'email' => 'customer@example.test', 'organization' => 'Trailbound Project',
            'phone' => '+44 7700 900123',
            'product_type' => 'Existing products', 'estimated_quantity' => '4',
            'sizes_needed' => 'One size', 'artwork_details' => 'Heat transfer artwork supplied',
            'needed_by' => now()->addMonth()->toDateString(),
            'country' => 'United Kingdom', 'shipping_address' => 'Portsmouth PO1 1AA',
            'status' => 'new', 'preferred_shipping_method' => 'standard',
            'product_information' => ['selected_products' => [[
                'name' => 'Skull cap', 'sku' => $product->sku, 'product_id' => $product->id,
                'quantity' => 4, 'options' => [['label' => 'Imprint method', 'value' => 'Heat transfer']],
            ]]],
        ]);
        return [$quote, $group, $option];
    }

    private function pricing(int $groupId, int|string $optionId, array $overrides = []): array
    {
        return array_replace_recursive([
            'shipping_method' => 'regular', 'sample_shipping_method' => 'regular',
            'private_address_charge' => '3', 'sample_private_charge' => '0',
            'lines' => [[
                'quantity' => '4', 'unit_price' => '5.50',
                'regular_shipping' => '10', 'express_shipping' => '20',
                'options' => [$groupId => (string) $optionId],
                'imprint_unit' => '0.25', 'imprint_setup' => '2',
                'sample_quantity' => '1', 'sample_type' => 'printed',
                'sample_unit' => '7', 'sample_imprint_unit' => '0.30', 'sample_setup' => '1',
                'sample_regular_shipping' => '2', 'sample_express_shipping' => '4',
            ]],
        ], $overrides);
    }
}
