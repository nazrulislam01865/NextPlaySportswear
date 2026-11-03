<?php

namespace Tests\Feature\Storefront;

use App\Http\Requests\Admin\PaymentPageRequest;
use App\Models\StorefrontPageSetting;
use App\Models\User;
use App\Services\Storefront\PaymentPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PaymentInformationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_prototype_copy_eight_sections_and_status_table_render_without_creating_a_record(): void
    {
        $response = $this->get(route('payment-information'));
        $response->assertOk()
            ->assertSee('Payment Information')
            ->assertSee('Payment methods and terms for your custom sportswear order.')
            ->assertSee('On this page')
            ->assertSee('Payment help and security')
            ->assertSee('Partially paid')
            ->assertSee('Read Returns &amp; Refunds', false)
            ->assertSee('Need help with a payment?')
            ->assertSee('Contact support')
            ->assertSee('Track your order');

        $this->assertSame(0, StorefrontPageSetting::query()->where('page_key', PaymentPageService::PAGE_KEY)->count());
    }

    public function test_payment_document_uses_the_returns_page_layout_and_eighth_sidebar_anchor(): void
    {
        $this->get(route('payment-information'))
            ->assertOk()
            ->assertSee('np-returns-page np-payment-page', false)
            ->assertSee('np-returns-breadcrumb', false)
            ->assertSee('np-returns-header', false)
            ->assertSee('np-returns-card np-payment-card', false)
            ->assertSee('np-guide-sticky-nav', false)
            ->assertSee('np-sidebar-nav', false)
            ->assertSee('href="#payment-section-8"', false)
            ->assertSee('id="payment-section-8"', false);

        $navigation = file_get_contents(public_path('js/nextplay-payment-navigation.js'));
        $this->assertStringContainsString('doc.scrollHeight - 4', $navigation);
        $this->assertStringContainsString('entries[entries.length - 1]', $navigation);
    }

    public function test_admin_can_update_all_types_of_copy_and_cache_is_invalidated(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = app(PaymentPageService::class)->defaults();
        $payload['hero']['title'] = 'Updated payment information';
        $payload['sections'][5]['title'] = 'Payment status explained';
        $payload['sections'][0]['nav_label'] = 'How to pay';
        $payload['status_table']['rows'][0]['cells'][1] = 'Payment accepted and recorded.';
        $payload['support']['title'] = 'Ask us about payment';
        $payload['seo']['meta_title'] = 'Custom Payment Page | NextPlay';

        Cache::put(PaymentPageService::CACHE_KEY, app(PaymentPageService::class)->defaults(), 3600);
        $this->actingAs($admin, 'admin')
            ->put(route('admin.payment-page.update'), $payload)
            ->assertRedirect(route('admin.payment-page.edit'));

        $this->assertSame('Updated payment information', StorefrontPageSetting::query()->where('page_key', PaymentPageService::PAGE_KEY)->firstOrFail()->title);
        $this->assertNull(Cache::get(PaymentPageService::CACHE_KEY));
        $this->get(route('payment-information'))
            ->assertOk()
            ->assertSee('Updated payment information')
            ->assertSee('How to pay')
            ->assertSee('Payment status explained')
            ->assertSee('Payment accepted and recorded.')
            ->assertSee('Ask us about payment')
            ->assertSee('Custom Payment Page | NextPlay');
    }

    public function test_status_table_is_row_wise_with_add_row_and_add_column_controls(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.payment-page.edit'))
            ->assertOk()
            ->assertSee('payment-status-grid', false)
            ->assertSee('payment-add-row', false)
            ->assertSee('payment-add-column', false)
            ->assertSee('status_table[columns][0]', false)
            ->assertSee('status_table[rows][0][cells][1]', false)
            ->assertSee('nextplay-payment-table-editor.js');
    }

    public function test_payment_status_rows_and_columns_can_be_added_edited_and_removed(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = app(PaymentPageService::class)->defaults();
        $payload['status_table']['columns'][] = 'Customer action';
        foreach ($payload['status_table']['rows'] as $index => &$row) {
            $row['cells'][] = $index === 0 ? 'Check your artwork.' : '';
        }
        unset($row);
        $payload['status_table']['rows'][] = ['cells' => ['On hold', 'Waiting for confirmation.', 'Contact support.']];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.payment-page.update'), $payload)
            ->assertRedirect(route('admin.payment-page.edit'));

        $this->get(route('payment-information'))->assertOk()
            ->assertSee('Customer action')
            ->assertSee('On hold')
            ->assertSee('Waiting for confirmation.')
            ->assertSee('Contact support.');

        $payload['status_table']['rows'] = [
            ['cells' => ['Paid', 'Payment received.', 'No action needed.']],
        ];
        $this->actingAs($admin, 'admin')
            ->put(route('admin.payment-page.update'), $payload)
            ->assertRedirect(route('admin.payment-page.edit'));

        $data = app(PaymentPageService::class)->settings()['status_table'];
        $this->assertCount(1, $data['rows']);
        $this->assertCount(3, $data['columns']);
        $this->get(route('payment-information'))->assertDontSee('On hold');
    }

    public function test_legacy_payment_status_settings_are_mapped_without_a_database_migration(): void
    {
        $legacy = [
            'status_heading' => 'Current status',
            'meaning_heading' => 'Details',
            'rows' => [
                ['status' => 'Settled', 'meaning' => 'Transaction is complete.'],
            ],
        ];
        StorefrontPageSetting::query()->create([
            'page_key' => PaymentPageService::PAGE_KEY,
            'title' => 'Payment Information',
            'content' => ['status_table' => $legacy],
            'seo' => [],
        ]);
        Cache::forget(PaymentPageService::CACHE_KEY);
        $table = app(PaymentPageService::class)->settings()['status_table'];
        $this->assertSame(['Current status', 'Details'], $table['columns']);
        $this->assertSame([['cells' => ['Settled', 'Transaction is complete.']]], $table['rows']);
    }

    public function test_payment_status_table_validation_requires_matching_column_counts(): void
    {
        $request = new PaymentPageRequest();
        $payload = app(PaymentPageService::class)->defaults();
        $payload['status_table']['columns'][] = 'Extra';
        $request->merge($payload);
        $invalid = Validator::make($payload, $request->rules());
        $request->withValidator($invalid);
        $this->assertTrue($invalid->fails());
        $payload['status_table']['rows'][0]['cells'][] = 'Additional detail';
        foreach (array_slice($payload['status_table']['rows'], 1) as $index => $row) {
            $payload['status_table']['rows'][$index + 1]['cells'][] = '';
        }
        $request->replace($payload);
        $valid = Validator::make($payload, $request->rules());
        $request->withValidator($valid);
        $this->assertTrue($valid->passes(), implode('; ', $valid->errors()->all()));
    }

    public function test_admin_payload_validates_fixed_prototype_and_rejects_unsafe_links(): void
    {
        $request = new PaymentPageRequest();
        $defaults = app(PaymentPageService::class)->defaults();
        $valid = Validator::make($defaults, $request->rules());
        $this->assertTrue($valid->passes(), json_encode($valid->errors()->all()));

        foreach (['javascript:alert(1)', '//bad.example', 'https://example.com', 'relative-path'] as $unsafe) {
            $payload = $defaults;
            $payload['support']['primary_href'] = $unsafe;
            $validator = Validator::make($payload, $request->rules());
            $this->assertTrue($validator->fails(), $unsafe);
        }

        $defaults['sections'] = array_slice($defaults['sections'], 0, 7);
        $this->assertTrue(Validator::make($defaults, $request->rules())->fails());
    }

    public function test_public_and_admin_payment_blade_templates_compile_to_valid_php(): void
    {
        foreach ([
            'storefront/content/payment.blade.php',
            'components/storefront/content/document-section.blade.php',
            'admin/payment-page/edit.blade.php',
        ] as $template) {
            $compiled = Blade::compileString((string) file_get_contents(resource_path('views/'.$template)));
            $file = tempnam(sys_get_temp_dir(), 'np-payment-');
            try {
                file_put_contents($file, $compiled);
                $process = new Process([PHP_BINARY, '-l', $file]);
                $process->run();
                $this->assertTrue($process->isSuccessful(), $template.': '.$process->getErrorOutput());
            } finally {
                @unlink($file);
            }
        }
    }
}
