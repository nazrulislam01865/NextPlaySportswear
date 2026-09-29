<?php
namespace Tests\Unit\Services\Storefront;

use App\Models\SustainabilityPageSetting;
use App\Services\Storefront\SustainabilityPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SustainabilityPageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_match_approved_prototype(): void
    {
        $p=app(SustainabilityPageService::class)->settings();
        $this->assertSame('SUSTAINABILITY',$p['hero']['title']);
        $this->assertSame('OUR APPROACH',$p['approach']['title']);
        $this->assertSame(['materials_waste','people_partners','packaging_delivery'],array_column($p['features'],'id'));
        $this->assertSame(['materials','partners','packaging'],array_column($p['informed']['cards'],'id'));
        $this->assertSame('/contact-us',$p['cta']['primary_url']);
        $this->assertSame('/products',$p['cta']['secondary_url']);
    }

    public function test_missing_table_returns_defaults(): void
    {
        Schema::dropIfExists('sustainability_page_settings');
        $this->assertSame('SUSTAINABILITY',app(SustainabilityPageService::class)->settings()['hero']['title']);
    }

    public function test_corrupt_fixed_collections_and_unsafe_urls_normalize(): void
    {
        SustainabilityPageSetting::query()->create([
            'hero'=>['title'=>['bad']],
            'features'=>[['id'=>'packaging_delivery','heading'=>'Moved'],['id'=>'packaging_delivery','heading'=>'Dup'],['id'=>'unknown','heading'=>'Bad'],['id'=>'materials_waste','heading'=>'Managed Materials']],
            'informed'=>['cards'=>[['id'=>'packaging','title'=>'Moved'],['id'=>'materials','title'=>'Managed Materials']]],
            'cta'=>['primary_url'=>'javascript:alert(1)','secondary_url'=>'//evil.example'],
        ]);
        $p=app(SustainabilityPageService::class)->settings();
        $this->assertSame('SUSTAINABILITY',$p['hero']['title']);
        $this->assertSame(['materials_waste','people_partners','packaging_delivery'],array_column($p['features'],'id'));
        $this->assertSame('Managed Materials',$p['features'][0]['heading']);
        $this->assertSame(['materials','partners','packaging'],array_column($p['informed']['cards'],'id'));
        $this->assertSame('/contact-us',$p['cta']['primary_url']);
        $this->assertSame('/products',$p['cta']['secondary_url']);
    }

    public function test_missing_and_foreign_media_use_code_owned_fallbacks(): void
    {
        Storage::fake('public');
        SustainabilityPageSetting::query()->create(['features'=>[['id'=>'materials_waste','image_path'=>'sustainability-page/features/images/missing.png']], 'informed'=>['cards'=>[['id'=>'materials','icon_path'=>'about-page/help/icons/x.png']]]]);
        $p=app(SustainabilityPageService::class)->settings();
        $this->assertFalse($p['features'][0]['has_custom_image']);
        $this->assertStringContainsString('images/sustainability/materials-waste.webp',$p['features'][0]['image_url']);
        $this->assertNull($p['informed']['cards'][0]['icon_url']);
        $this->assertSame('leaf',$p['informed']['cards'][0]['fallback_icon']);
    }
}
