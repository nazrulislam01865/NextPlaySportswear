<?php

namespace Tests\Unit;

use App\Models\HomepageSlide;
use App\Services\Catalog\HomepageSlideMediaService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImageRemovalPrecedenceTest extends TestCase
{
    public function test_removing_an_image_does_not_restore_the_old_url_field(): void
    {
        $slide = \Mockery::mock(HomepageSlide::class)->makePartial();
        $slide->image_path = null;
        $slide->image_url = 'https://example.com/old-banner.jpg';
        $slide->shouldReceive('save')->once();

        $request = Request::create('/admin/slide', 'PUT', [
            'remove_image' => '1',
            'image_url' => 'https://example.com/old-banner.jpg',
        ]);

        (new HomepageSlideMediaService())->sync($slide, $request);

        $this->assertNull($slide->image_path);
        $this->assertNull($slide->image_url);
    }
}
