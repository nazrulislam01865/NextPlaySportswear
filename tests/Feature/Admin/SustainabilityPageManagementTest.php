<?php
namespace Tests\Feature\Admin;

use App\Models\SustainabilityPageSetting;
use App\Models\User;
use App\Services\Storefront\SustainabilityPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SustainabilityPageManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); AdminRbac::syncDefaults(true); }
    public function test_admin_can_save_content_and_all_six_media_slots(): void { Storage::fake('public'); $u=User::factory()->create(['role'=>'super_admin','is_active'=>true]); $p=$this->payload(); $p['hero']['title']='MANAGED SUSTAINABILITY'; for($i=0;$i<3;$i++){ $p['feature_image_'.$i]=UploadedFile::fake()->image("f$i.png"); $p['info_card_icon_'.$i]=UploadedFile::fake()->image("i$i.png"); } $this->actingAs($u,'admin')->put(route('admin.sustainability-page.update'),$p)->assertRedirect(route('admin.sustainability-page.edit')); $s=SustainabilityPageSetting::query()->firstOrFail(); $this->assertSame('MANAGED SUSTAINABILITY',$s->hero['title']); $this->assertStringStartsWith('sustainability-page/features/images/',$s->features[0]['image_path']); $this->assertStringStartsWith('sustainability-page/informed/icons/',$s->informed['cards'][2]['icon_path']); }
    public function test_failed_database_save_rolls_back_staged_upload(): void { Storage::fake('public'); Storage::disk('public')->put('sustainability-page/features/images/current.png','current'); $d=app(SustainabilityPageService::class)->defaults(); $d['features'][0]['image_path']='sustainability-page/features/images/current.png'; SustainabilityPageSetting::query()->create($d); $u=User::factory()->create(['role'=>'super_admin','is_active'=>true]); $p=$this->payload(); $p['feature_image_0']=UploadedFile::fake()->image('new.png'); $u->delete(); $this->actingAs($u,'admin')->put(route('admin.sustainability-page.update'),$p); Storage::disk('public')->assertExists('sustainability-page/features/images/current.png'); $this->assertCount(1,Storage::disk('public')->allFiles('sustainability-page/features/images')); }
    private function payload(): array { $p=app(SustainabilityPageService::class)->defaults(); foreach($p['features'] as &$f){unset($f['image_path'],$f['fallback_image']);} unset($f); foreach($p['informed']['cards'] as &$c){unset($c['icon_path'],$c['fallback_icon']);} unset($c); return $p; }
}
