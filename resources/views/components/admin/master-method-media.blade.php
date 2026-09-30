@props(['method'])

<x-admin.section-card title="Image / Icon" description="Optional image or icon used for this option on the storefront. Uploading a file takes priority over an external URL.">
    <div class="grid gap-5 lg:grid-cols-[180px_minmax(0,1fr)]">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            @if($method->imageUrl())
                <img src="{{ $method->imageUrl() }}" alt="{{ $method->name ?: 'Method image' }}" class="h-32 w-full object-contain">
            @else
                <div class="grid h-32 place-items-center text-sm font-semibold text-slate-400">No image uploaded</div>
            @endif
        </div>
        <div class="grid gap-4">
            <label class="admin-label">
                Upload image / icon
                <input type="file" name="image_file" class="admin-input" accept="image/jpeg,image/png,image/webp,image/avif">
                <span class="mt-2 block text-xs font-medium text-slate-500">JPG, PNG, WebP or AVIF. Maximum 3 MB.</span>
            </label>
            <label class="admin-label">
                External image URL
                <input type="url" name="image_url" value="{{ old('image_url', $method->image_url) }}" class="admin-input" maxlength="2048" placeholder="https://example.com/icon.png">
            </label>
            @if($method->image_path || $method->image_url)
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="remove_image" value="0">
                    <input type="checkbox" name="remove_image" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                    Remove current image / icon
                </label>
            @else
                <input type="hidden" name="remove_image" value="0">
            @endif
        </div>
    </div>
</x-admin.section-card>
