@php
    $isEdit = $slide->exists;
    $currentImage = \App\Support\PublicMedia::url($slide->image_path, $slide->image_url, '');
    $checked = static fn (string $field, bool $default = false): bool => old($field) !== null
        ? filter_var(old($field), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($slide->{$field} ?? $default);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-6"
    x-data="{
        imageUrl: @js(old('image_url', $slide->image_url)),
        imagePreview: @js($currentImage),
        removeImage: @js($checked('remove_image')),
        showContent: @js($checked('show_content', true)),
        previewFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
            if (!allowed.includes(file.type) || file.size > 10 * 1024 * 1024) {
                event.target.value = '';
                window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 10 MB.');
                return;
            }
            if (String(this.imagePreview).startsWith('blob:')) URL.revokeObjectURL(this.imagePreview);
            this.removeImage = false;
            this.imagePreview = URL.createObjectURL(file);
        },
        previewUrl() {
            if (!String(this.imagePreview).startsWith('blob:')) this.imagePreview = this.imageUrl;
            this.removeImage = false;
        }
    }"
>
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <x-admin.section-card title="Slide Image" description="Upload an optimized banner image or use a secure remote image URL. Uploaded images take priority.">
        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="admin-label sm:col-span-2">
                    Upload image
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif" class="admin-input h-auto py-3" x-on:change="previewFile($event)">
                    <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">Landscape image, minimum 800×350 pixels. JPG, PNG, WebP or AVIF, maximum 10 MB.</span>
                </label>

                <label class="admin-label sm:col-span-2">
                    Or image URL
                    <input type="url" name="image_url" x-model="imageUrl" x-on:input.debounce.400ms="previewUrl()" value="{{ old('image_url', $slide->image_url) }}" class="admin-input" placeholder="https://example.com/banner.webp" maxlength="2048">
                </label>

                <label class="admin-label sm:col-span-2">
                    Image alt text
                    <input type="text" name="image_alt" value="{{ old('image_alt', $slide->image_alt) }}" class="admin-input" maxlength="255" placeholder="Describe the image for accessibility and SEO">
                </label>

                <label class="admin-label">
                    Image focal point
                    <select name="image_focal_position" class="admin-input">
                        @foreach(['center' => 'Center', 'top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right', 'top-left' => 'Top left', 'top-right' => 'Top right', 'bottom-left' => 'Bottom left', 'bottom-right' => 'Bottom right'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('image_focal_position', $slide->image_focal_position ?: 'center') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if($isEdit && $currentImage)
                    <label class="flex items-center gap-3 self-end rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                        <input type="hidden" name="remove_image" value="0">
                        <input type="checkbox" name="remove_image" value="1" x-model="removeImage" x-on:change="if (removeImage) { imageUrl = ''; imagePreview = ''; }" class="h-4 w-4 rounded border-red-300 text-brand-red">
                        Remove existing image
                    </label>
                @else
                    <input type="hidden" name="remove_image" value="0">
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <div class="aspect-[16/7] min-h-[190px]" x-show="!removeImage && imagePreview">
                    <img :src="imagePreview" alt="Slide image preview" class="h-full w-full object-cover" x-on:error="$el.style.display='none'" x-on:load="$el.style.display='block'">
                </div>
                <div class="grid min-h-[190px] place-items-center p-6 text-center text-sm font-bold text-slate-500" x-show="removeImage || !imagePreview">
                    Image preview will appear here
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Text Content" description="Customize every line or turn the complete text overlay off for an image-only slide.">
        <div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <label class="flex items-start gap-3 text-sm font-black text-brand-dark">
                <input type="hidden" name="show_content" value="0">
                <input type="checkbox" name="show_content" value="1" x-model="showContent" class="mt-0.5 h-5 w-5 rounded border-blue-300 text-brand-blue">
                <span>Show text and buttons on this slide<span class="mt-1 block text-xs font-medium leading-5 text-slate-600">Turn this off to mute all slider text and display only the image.</span></span>
            </label>
        </div>

        <div class="grid gap-5" :class="!showContent && 'opacity-50'">
            <div class="grid gap-4 sm:grid-cols-[170px_1fr]">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold">
                    <input type="hidden" name="show_eyebrow" value="0">
                    <input type="checkbox" name="show_eyebrow" value="1" @checked($checked('show_eyebrow', true)) class="h-4 w-4 rounded border-slate-300 text-brand-red">
                    Show eyebrow
                </label>
                <label class="admin-label">Eyebrow text<input type="text" name="eyebrow" value="{{ old('eyebrow', $slide->eyebrow) }}" class="admin-input" maxlength="160" placeholder="Custom Jerseys USA"></label>
            </div>

            <div class="grid gap-4 sm:grid-cols-[170px_1fr]">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold">
                    <input type="hidden" name="show_title" value="0">
                    <input type="checkbox" name="show_title" value="1" @checked($checked('show_title', true)) class="h-4 w-4 rounded border-slate-300 text-brand-red">
                    Show title
                </label>
                <label class="admin-label">Title<input type="text" name="title" value="{{ old('title', $slide->title) }}" class="admin-input" maxlength="255" placeholder="Build Your Team Jersey"></label>
            </div>

            <div class="grid gap-4 sm:grid-cols-[170px_1fr]">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold">
                    <input type="hidden" name="show_description" value="0">
                    <input type="checkbox" name="show_description" value="1" @checked($checked('show_description', true)) class="h-4 w-4 rounded border-slate-300 text-brand-red">
                    Show description
                </label>
                <label class="admin-label">Description<textarea name="description" class="admin-textarea" maxlength="2000" placeholder="Explain the promotion in one or two short sentences.">{{ old('description', $slide->description) }}</textarea></label>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Slider Buttons" description="Each button can link to an internal page, section anchor, or secure external URL.">
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-5">
                <label class="mb-4 flex items-center gap-3 text-sm font-black">
                    <input type="hidden" name="show_primary_button" value="0">
                    <input type="checkbox" name="show_primary_button" value="1" @checked($checked('show_primary_button', true)) class="h-4 w-4 rounded border-slate-300 text-brand-red">
                    Show primary button
                </label>
                <div class="space-y-4">
                    <label class="admin-label">Button label<input type="text" name="primary_label" value="{{ old('primary_label', $slide->primary_label) }}" class="admin-input" maxlength="160" placeholder="Shop Now"></label>
                    <label class="admin-label">Destination<input type="text" name="primary_url" value="{{ old('primary_url', $slide->primary_url) }}" class="admin-input" maxlength="2048" placeholder="/products or #products"></label>
                    <label class="admin-label">Open in<select name="primary_target" class="admin-input"><option value="_self" @selected(old('primary_target', $slide->primary_target ?: '_self') === '_self')>Same tab</option><option value="_blank" @selected(old('primary_target', $slide->primary_target) === '_blank')>New tab</option></select></label>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-5">
                <label class="mb-4 flex items-center gap-3 text-sm font-black">
                    <input type="hidden" name="show_secondary_button" value="0">
                    <input type="checkbox" name="show_secondary_button" value="1" @checked($checked('show_secondary_button')) class="h-4 w-4 rounded border-slate-300 text-brand-red">
                    Show secondary button
                </label>
                <div class="space-y-4">
                    <label class="admin-label">Button label<input type="text" name="secondary_label" value="{{ old('secondary_label', $slide->secondary_label) }}" class="admin-input" maxlength="160" placeholder="Request a Quote"></label>
                    <label class="admin-label">Destination<input type="text" name="secondary_url" value="{{ old('secondary_url', $slide->secondary_url) }}" class="admin-input" maxlength="2048" placeholder="/quote-request or #bulk"></label>
                    <label class="admin-label">Open in<select name="secondary_target" class="admin-input"><option value="_self" @selected(old('secondary_target', $slide->secondary_target ?: '_self') === '_self')>Same tab</option><option value="_blank" @selected(old('secondary_target', $slide->secondary_target) === '_blank')>New tab</option></select></label>
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Appearance" description="Control where the text appears and how strongly the image is shaded behind it.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            <label class="admin-label">Content position<select name="content_position" class="admin-input">@foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)<option value="{{ $value }}" @selected(old('content_position', $slide->content_position ?: 'left') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="admin-label">Text alignment<select name="text_alignment" class="admin-input">@foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)<option value="{{ $value }}" @selected(old('text_alignment', $slide->text_alignment ?: 'left') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="admin-label">Text theme<select name="text_theme" class="admin-input"><option value="light" @selected(old('text_theme', $slide->text_theme ?: 'light') === 'light')>Light text</option><option value="dark" @selected(old('text_theme', $slide->text_theme) === 'dark')>Dark text</option></select></label>
            <label class="admin-label">Overlay color<div class="mt-2 flex h-12 overflow-hidden rounded-xl border border-slate-300 bg-white"><input type="color" value="{{ old('overlay_color', $slide->overlay_color ?: '#0D2545') }}" class="h-full w-14 border-0 p-1" oninput="this.nextElementSibling.value=this.value.toUpperCase()"><input type="text" name="overlay_color" value="{{ old('overlay_color', $slide->overlay_color ?: '#0D2545') }}" class="min-w-0 flex-1 border-0 px-3 text-sm font-bold uppercase outline-none" maxlength="7" pattern="#[0-9A-Fa-f]{6}"></div></label>
            <label class="admin-label">Overlay opacity<input type="number" name="overlay_opacity" value="{{ old('overlay_opacity', $slide->overlay_opacity ?? 72) }}" class="admin-input" min="0" max="100" step="1"><span class="mt-2 block text-xs font-medium text-slate-500">0 = none, 100 = solid</span></label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Publishing" description="Set display order, active status, and an optional publication window.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">Sort order<input type="number" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" class="admin-input" min="0" max="1000000"></label>
            <label class="admin-label">Starts at<input type="datetime-local" name="starts_at" value="{{ old('starts_at', $slide->starts_at?->format('Y-m-d\TH:i')) }}" class="admin-input"></label>
            <label class="admin-label">Ends at<input type="datetime-local" name="ends_at" value="{{ old('ends_at', $slide->ends_at?->format('Y-m-d\TH:i')) }}" class="admin-input"></label>
            <label class="flex items-center gap-3 self-end rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($checked('is_active', true)) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                Active on storefront
            </label>
        </div>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
        <a href="{{ route('admin.homepage-slides.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Slide' : 'Create Slide' }}</button>
    </div>
</form>
