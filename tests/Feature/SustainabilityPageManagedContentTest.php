<?php
namespace Tests\Feature;

use App\Models\SustainabilityPageSetting;
use App\Services\Storefront\SustainabilityPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SustainabilityPageManagedContentTest extends TestCase
{
    use RefreshDatabase;
    public function test_storefront_renders_managed_content_with_fixed_three_plus_three_structure(): void { $p=app(SustainabilityPageService::class)->defaults(); $p['hero']['title']='MANAGED SUSTAINABILITY'; $p['features'][0]['heading']='MANAGED MATERIALS'; $p['informed']['cards'][0]['title']='Managed Card'; $p['cta']['primary_label']='MANAGED CONTACT'; $p['seo']=['title'=>'Managed Sustainability SEO','description'=>'Managed sustainability description']; SustainabilityPageSetting::query()->create($p); $r=$this->get(route('sustainability'))->assertOk()->assertSee('MANAGED SUSTAINABILITY')->assertSee('MANAGED MATERIALS')->assertSee('Managed Card')->assertSee('MANAGED CONTACT')->assertSee('Managed Sustainability SEO'); $html=$r->getContent(); $this->assertSame(3,substr_count($html,'sustainability-page__feature-row')); $this->assertSame(3,substr_count($html,'class="sustainability-page__info-card"')); }
}
