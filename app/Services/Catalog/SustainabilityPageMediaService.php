<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\SustainabilityPageRequest;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SustainabilityPageMediaService
{
    /** @param array<string,mixed> $current @param array<string,mixed> $payload @return array<string,mixed> */
    public function prepare(SustainabilityPageRequest $request, array $current, array $payload): array
    {
        $mutation=['payload'=>$payload,'new_paths'=>[],'old_paths'=>[],'delete_after_commit'=>[]];
        try {
            foreach ($this->slots() as $slot) {
                $currentPath=data_get($current,$slot['data_path']);
                $currentPath=is_string($currentPath)&&trim($currentPath)!==''?PublicMedia::normalizePath($currentPath):null;
                data_set($mutation['payload'],$slot['data_path'],$currentPath);
                $file=$request->file($slot['file']);
                if ($file instanceof UploadedFile) {
                    $new=$file->store($slot['directory'],'public');
                    if (! is_string($new)||$new==='') throw new RuntimeException('Unable to store Sustainability page media.');
                    $new=PublicMedia::normalizePath($new); data_set($mutation['payload'],$slot['data_path'],$new); $mutation['new_paths'][]=$new;
                    if ($currentPath && $currentPath!==$new && $this->isSustainabilityOwnedPath($currentPath)) { $mutation['old_paths'][]=$currentPath; $mutation['delete_after_commit'][]=$currentPath; }
                    continue;
                }
                if ($request->boolean($slot['remove'])) { data_set($mutation['payload'],$slot['data_path'],null); if ($currentPath && $this->isSustainabilityOwnedPath($currentPath)) { $mutation['old_paths'][]=$currentPath; $mutation['delete_after_commit'][]=$currentPath; } }
            }
        } catch (Throwable $e) { $this->rollback($mutation); throw $e; }
        foreach (['new_paths','old_paths','delete_after_commit'] as $k) $mutation[$k]=array_values(array_unique($mutation[$k]));
        return $mutation;
    }
    public function rollback(array $mutation): void { $this->deleteOwnedPaths($mutation['new_paths']??[]); }
    public function commitCleanup(array $mutation): void { $this->deleteOwnedPaths($mutation['delete_after_commit']??$mutation['old_paths']??[]); }
    public function isSustainabilityOwnedPath(string $path): bool { $p=PublicMedia::normalizePath($path); if (!str_starts_with($p,'sustainability-page/')||str_contains($p,"\0")) return false; foreach(explode('/',$p) as $s) if($s===''||$s==='.'||$s==='..') return false; return count(explode('/',$p))>=3; }
    /** @return array<int,array<string,string>> */
    private function slots(): array { $slots=[]; for($i=0;$i<3;$i++) $slots[]=['file'=>'feature_image_'.$i,'remove'=>'remove_feature_image_'.$i,'data_path'=>'features.'.$i.'.image_path','directory'=>'sustainability-page/features/images']; for($i=0;$i<3;$i++) $slots[]=['file'=>'info_card_icon_'.$i,'remove'=>'remove_info_card_icon_'.$i,'data_path'=>'informed.cards.'.$i.'.icon_path','directory'=>'sustainability-page/informed/icons']; return $slots; }
    private function deleteOwnedPaths(array $paths): void { foreach(array_unique($paths) as $p) if(is_string($p)&&$this->isSustainabilityOwnedPath($p)) Storage::disk('public')->delete(PublicMedia::normalizePath($p)); }
}
