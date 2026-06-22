<?php

namespace Tests\Unit;

use App\Services\Security\SafeHtmlService;
use PHPUnit\Framework\TestCase;

class SafeHtmlServiceTest extends TestCase
{
    public function test_it_preserves_formatting_and_removes_executable_markup(): void
    {
        $service = new SafeHtmlService();
        $clean = $service->sanitize('<h2 onclick="alert(1)">Details</h2><script>alert(1)</script><a href="javascript:alert(1)">Bad</a><a href="/safe" target="_blank">Safe</a>');

        $this->assertStringContainsString('<h2>Details</h2>', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('href="/safe"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }
}
