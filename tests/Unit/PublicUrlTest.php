<?php

namespace Tests\Unit;

use App\Support\PublicUrl;
use PHPUnit\Framework\TestCase;

class PublicUrlTest extends TestCase
{
    public function test_it_accepts_safe_public_destinations(): void
    {
        $this->assertTrue(PublicUrl::isAllowed('/category/custom-jerseys'));
        $this->assertTrue(PublicUrl::isAllowed('https://example.com/catalog'));
        $this->assertTrue(PublicUrl::isAllowed('#products'));
        $this->assertTrue(PublicUrl::isAllowed('mailto:sales@example.com', true));
    }

    public function test_it_rejects_executable_or_protocol_relative_destinations(): void
    {
        $this->assertFalse(PublicUrl::isAllowed('javascript:alert(1)'));
        $this->assertFalse(PublicUrl::isAllowed('//evil.example/path'));
        $this->assertFalse(PublicUrl::isAllowed('data:text/html,unsafe'));
        $this->assertFalse(PublicUrl::isAllowed("/safe\nLocation: https://evil.example"));
    }
}
