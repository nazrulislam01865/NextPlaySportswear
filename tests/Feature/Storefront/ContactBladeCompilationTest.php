<?php

namespace Tests\Feature\Storefront;

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ContactBladeCompilationTest extends TestCase
{
    public function test_contact_page_blade_compiles_to_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/storefront/content/contact.blade.php'));
        $this->assertNotFalse($source);

        // Both the contact information link and the social icon use the URL
        // already populated by ContactPageService (including its fallback).
        $this->assertSame(2, substr_count($source, "{{ data_get(\$content, 'info.whatsapp_url') }}"));

        $compiled = Blade::compileString($source);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'nextplay-contact-blade-');
        $this->assertNotFalse($temporaryFile);

        try {
            file_put_contents($temporaryFile, $compiled);
            $process = new Process([PHP_BINARY, '-l', $temporaryFile]);
            $process->run();

            $this->assertTrue(
                $process->isSuccessful(),
                "Contact Us Blade PHP did not compile cleanly:\n".$process->getOutput().$process->getErrorOutput()
            );
        } finally {
            @unlink($temporaryFile);
        }
    }
}
