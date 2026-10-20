<?php

namespace Tests\Unit\Support;

use App\Support\ViewerTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class ViewerTimeTest extends TestCase
{
    public function test_generates_an_utc_instant_for_browser_localization(): void
    {
        $rendered = ViewerTime::render(CarbonImmutable::parse('2026-10-20 11:34:26', 'Asia/Dhaka'), 'M j, Y · g:i A');

        $this->assertInstanceOf(HtmlString::class, $rendered);
        $this->assertStringContainsString('datetime="2026-10-20T05:34:26.000000Z"', $rendered->toHtml());
        $this->assertStringContainsString('data-np-local-time', $rendered->toHtml());
        $this->assertStringContainsString('Oct 20, 2026 · 5:34 AM', $rendered->toHtml());
    }

    public function test_it_does_not_mutate_source_carbon_or_app_timezone(): void
    {
        $original = CarbonImmutable::parse('2026-10-20 11:34:26', 'Asia/Dhaka');
        ViewerTime::render($original);

        $this->assertSame('Asia/Dhaka', $original->getTimezone()->getName());
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_null_dates_remain_null_and_untrusted_format_is_escaped(): void
    {
        $this->assertNull(ViewerTime::render(null));
        $html = ViewerTime::render('2026-10-20 00:00:00', 'M j, Y" onmouseover="alert(1)')->toHtml();
        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertStringContainsString('&quot;', $html);
    }
}
