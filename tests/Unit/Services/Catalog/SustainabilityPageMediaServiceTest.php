<?php
namespace Tests\Unit\Services\Catalog;

use App\Http\Requests\Admin\SustainabilityPageRequest;
use App\Services\Catalog\SustainabilityPageMediaService;
use App\Services\Storefront\SustainabilityPageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SustainabilityPageMediaServiceTest extends TestCase
{
    public function test_all_six_slots_store_inside_sustainability_namespace(): void
    {
        Storage::fake('public');
        $files=[]; for($i=0;$i<3;$i++){ $files['feature_image_'.$i]=UploadedFile::fake()->image("f$i.png"); $files['info_card_icon_'.$i]=UploadedFile::fake()->image("i$i.png"); }
        $mutation=app(SustainabilityPageMediaService::class)->prepare(SustainabilityPageRequest::create('/admin/sustainability-page','PUT',[],[],$files),app(SustainabilityPageService::class)->defaults(),$this->payload());
        $this->assertCount(6,$mutation['new_paths']);
        $this->assertStringStartsWith('sustainability-page/features/images/',$mutation['payload']['features'][2]['image_path']);
        $this->assertStringStartsWith('sustainability-page/informed/icons/',$mutation['payload']['informed']['cards'][2]['icon_path']);
    }

    public function test_cleanup_and_rollback_never_delete_foreign_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sustainability-page/features/images/old.png','old'); Storage::disk('public')->put('branding/logo.png','brand');
        $current=app(SustainabilityPageService::class)->defaults(); $current['features'][0]['image_path']='sustainability-page/features/images/old.png';
        $request=SustainabilityPageRequest::create('/admin/sustainability-page','PUT',['remove_feature_image_0'=>'1']);
        $mutation=app(SustainabilityPageMediaService::class)->prepare($request,$current,$this->payload());
        app(SustainabilityPageMediaService::class)->commitCleanup($mutation+['delete_after_commit'=>array_merge($mutation['old_paths'],['branding/logo.png'])]);
        Storage::disk('public')->assertMissing('sustainability-page/features/images/old.png'); Storage::disk('public')->assertExists('branding/logo.png');
        $this->assertFalse(app(SustainabilityPageMediaService::class)->isSustainabilityOwnedPath('sustainability-page/../branding/logo.png'));
    }

    public function test_rollback_deletes_new_staged_file_and_preserves_previous_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sustainability-page/features/images/current.png','current');
        $current=app(SustainabilityPageService::class)->defaults();
        $current['features'][0]['image_path']='sustainability-page/features/images/current.png';
        $mutation=app(SustainabilityPageMediaService::class)->prepare(
            SustainabilityPageRequest::create('/admin/sustainability-page','PUT',[],[],['feature_image_0'=>UploadedFile::fake()->image('new.png')]),
            $current,
            $this->payload()
        );
        $new=$mutation['payload']['features'][0]['image_path'];
        Storage::disk('public')->assertExists($new);
        app(SustainabilityPageMediaService::class)->rollback($mutation);
        Storage::disk('public')->assertMissing($new);
        Storage::disk('public')->assertExists('sustainability-page/features/images/current.png');
    }

    private function payload(): array
    {
        $p=app(SustainabilityPageService::class)->defaults(); foreach($p['features'] as &$f){unset($f['image_path'],$f['fallback_image']);} unset($f); foreach($p['informed']['cards'] as &$c){unset($c['icon_path'],$c['fallback_icon']);} unset($c); return $p;
    }
}
