<?php
namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\SustainabilityPageRequest;
use App\Models\User;
use App\Services\Storefront\SustainabilityPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SustainabilityPageValidationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); AdminRbac::syncDefaults(true); }
    public function test_fixed_ids_and_unsafe_urls_are_rejected(): void { $u=User::factory()->create(['role'=>'super_admin','is_active'=>true]); $p=app(SustainabilityPageService::class)->defaults(); foreach($p['features'] as &$f){unset($f['image_path'],$f['fallback_image']);} unset($f); foreach($p['informed']['cards'] as &$c){unset($c['icon_path'],$c['fallback_icon']);} unset($c); $p['features'][0]['id']='packaging_delivery'; $p['cta']['primary_url']='javascript:alert(1)'; $this->actingAs($u,'admin')->from(route('admin.sustainability-page.edit'))->put(route('admin.sustainability-page.update'),$p)->assertSessionHasErrors(['features','cta.primary_url']); }

    public function test_invalid_and_oversized_media_are_rejected(): void { $u=User::factory()->create(['role'=>'super_admin','is_active'=>true]); $p=app(SustainabilityPageService::class)->defaults(); foreach($p['features'] as &$f){unset($f['image_path'],$f['fallback_image']);} unset($f); foreach($p['informed']['cards'] as &$c){unset($c['icon_path'],$c['fallback_icon']);} unset($c); $p['feature_image_0']=UploadedFile::fake()->create('bad.pdf',10,'application/pdf'); $p['info_card_icon_0']=UploadedFile::fake()->image('large.png')->size(3000); $this->actingAs($u,'admin')->from(route('admin.sustainability-page.edit'))->put(route('admin.sustainability-page.update'),$p)->assertSessionHasErrors(['feature_image_0','info_card_icon_0']); }
    public function test_request_declares_exact_fixed_ids(): void { $this->assertSame(['materials_waste','people_partners','packaging_delivery'],SustainabilityPageRequest::FEATURE_IDS); $this->assertSame(['materials','partners','packaging'],SustainabilityPageRequest::INFO_CARD_IDS); }
}
