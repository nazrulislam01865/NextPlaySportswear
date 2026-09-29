<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SustainabilityPageRequest;
use App\Models\SustainabilityPageSetting;
use App\Services\Catalog\SustainabilityPageMediaService;
use App\Services\Storefront\SustainabilityPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SustainabilityPageController extends Controller
{
    public function __construct(private readonly SustainabilityPageService $page, private readonly SustainabilityPageMediaService $media) {}
    public function edit(): View { return view('admin.sustainability-page.edit',['sustainability'=>$this->page->settings(),'canManageSustainabilityPage'=>(bool)(auth('admin')->user()?->canAdmin('sustainability_page.manage')??false)]); }
    public function update(SustainabilityPageRequest $request): RedirectResponse
    {
        $current=$this->page->settings(); $adminId=auth('admin')->id(); $mutation=null;
        try {
            $mutation=$this->media->prepare($request,$current,$request->validatedContent());
            DB::transaction(function() use($mutation,$adminId): void { $setting=SustainabilityPageSetting::query()->first()??new SustainabilityPageSetting(); $setting->fill($mutation['payload']); if(!$setting->exists)$setting->created_by=$adminId; $setting->updated_by=$adminId; $setting->save(); });
        } catch(Throwable $e) { if(is_array($mutation))$this->media->rollback($mutation); report($e); return redirect()->route('admin.sustainability-page.edit')->withInput()->withErrors(['sustainability_page'=>'The Sustainability page could not be saved. Your existing content and media were kept unchanged.']); }
        try { $this->media->commitCleanup($mutation); } catch(Throwable $e) { report($e); }
        return redirect()->route('admin.sustainability-page.edit')->with('status','Sustainability page updated successfully.');
    }
}
