<?php

namespace Tests\Feature;

use Tests\TestCase;

class PersonalizationSharedControlsSourceTest extends TestCase
{
    public function test_all_template_fields_render_in_shared_mode_and_remove_button_is_absent(): void
    {
        $source = file_get_contents(resource_path('views/components/storefront/product/builder.blade.php'));
        $this->assertStringContainsString('@foreach($enabledRosterFields as $field)', $source);
        $this->assertStringContainsString('name="personalization_shared_files[{{ $field[\'key\'] }}]"', $source);
        $this->assertStringContainsString('setPersonalizationSharedUpload', $source);
        $this->assertStringNotContainsString('class="np-roster-remove-button"', $source);
    }
}
