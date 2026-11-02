<?php

namespace Tests\Feature\Storefront;

use App\Services\Storefront\PrivacyPageService;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PrivacyPrototypeAndContactDropdownTest extends TestCase
{
    public function test_privacy_document_blade_compiles_to_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/storefront/content/privacy.blade.php'));
        $this->assertNotFalse($source);

        $compiled = Blade::compileString($source);
        $file = tempnam(sys_get_temp_dir(), 'np-privacy-blade-');
        $this->assertNotFalse($file);

        try {
            file_put_contents($file, $compiled);
            $result = new Process([PHP_BINARY, '-l', $file]);
            $result->run();
            $this->assertTrue($result->isSuccessful(), $result->getOutput().$result->getErrorOutput());
        } finally {
            @unlink($file);
        }
    }

    public function test_privacy_sections_are_admin_managed_without_hardcoding_body_copy_in_view(): void
    {
        $defaults = app(PrivacyPageService::class)->defaults();
        $this->assertCount(7, $defaults['sections']);
        $this->assertCount(2, explode("\n\n", $defaults['sections']['01']['content']));
        $this->assertSame('Home', $defaults['navigation']['home_label']);

        $view = file_get_contents(resource_path('views/storefront/content/privacy.blade.php'));
        $admin = file_get_contents(resource_path('views/admin/privacy-page/edit.blade.php'));
        $this->assertStringContainsString('@foreach($privacySections as $section)', $view);
        foreach (['hero[title]', 'hero[updated_label]', 'hero[subtitle]', 'hero[description]',
            'sections[{{ $secKey }}][title]', 'sections[{{ $secKey }}][content]',
            'navigation[home_label]', 'navigation[breadcrumb_label]', 'navigation[back_to_top_label]',
            'seo[meta_title]', 'seo[meta_description]'] as $field) {
            $this->assertStringContainsString($field, $admin);
        }
    }

    public function test_privacy_layout_uses_terms_document_and_breadcrumb_contract(): void
    {
        $privacy = file_get_contents(resource_path('views/storefront/content/privacy.blade.php'));
        $terms = file_get_contents(resource_path('views/storefront/content/terms.blade.php'));
        $layout = file_get_contents(resource_path('views/components/layouts/storefront.blade.php'));
        $privacyCss = file_get_contents(public_path('css/nextplay-privacy-prototype.css'));

        foreach ([$terms, $privacy] as $page) {
            $this->assertStringContainsString('class="site-container"', $page);
            $this->assertStringContainsString('class="nps-breadcrumb"', $page);
            $this->assertStringContainsString('np-legal-document', $page);
        }

        $this->assertStringContainsString("'terms', 'privacy'", $layout);
        $this->assertStringNotContainsString('max-width: 700px', $privacyCss);
        $this->assertStringContainsString('max-width: none', $privacyCss);
        $this->assertStringContainsString('np-privacy-document__updated', $privacy);
        $this->assertStringContainsString('@foreach($privacySections as $section)', $privacy);
    }

    public function test_contact_subject_keeps_native_select_and_centralized_chevron_style(): void
    {
        $view = file_get_contents(resource_path('views/storefront/content/contact.blade.php'));
        $css = file_get_contents(public_path('css/nextplay-content-design-system.css'));

        $this->assertStringContainsString('id="contact-topic"', $view);
        $this->assertStringContainsString('name="topic"', $view);
        $this->assertStringContainsString('select.np-select', $css);
        $this->assertStringContainsString('background-image: url(', $css);
        $this->assertStringContainsString('padding-right: 48px', $css);
    }
}
