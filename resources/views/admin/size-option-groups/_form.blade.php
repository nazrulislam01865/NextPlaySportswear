@php
    use App\Enums\SizeAudience;

    $isEdit = $group->exists;
    $initialSizes = $group->relationLoaded('sizes')
        ? $group->sizes->map(fn ($size) => ['label' => $size->label, 'code' => $size->code, 'is_active' => true, 'sort_order' => $size->sort_order])->values()->all()
        : [['label' => '', 'code' => '', 'is_active' => true, 'sort_order' => 0]];
@endphp

<div class="space-y-6">
    <x-admin.section-card title="Size Group" description="Create one reusable size group for Male, Female, Youth, Kids, Unisex, or another product audience.">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="admin-label">Name
                <input class="admin-input" name="name" value="{{ old('name', $group->name) }}" maxlength="160" placeholder="Example: Adult Male" required>
            </label>
            <label class="admin-label">Type
                <select class="admin-input" name="audience" required>
                    @foreach($audiences as $value => $label)
                        <option value="{{ $value }}" @selected(old('audience', $group->audience?->value ?? SizeAudience::Unisex->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-6">
            <x-admin.rich-editor name="description_html" :value="$group->description_html" label="Formatted size description" />
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Size Options" description="These sizes become available when the group is added to a product.">
        <x-admin.size-option-rows name="sizes" :sizes="$initialSizes" />
    </x-admin.section-card>

    <x-admin.section-card title="Size Chart" description="Provide either formatted size-chart content or one size-chart image. Both are optional, but only one method can be used at a time.">
        <div>
            <x-admin.rich-editor
                name="chart_html"
                :value="$group->chart_html"
                label="Size Chart Table / Information"
            />
            @error('chart_html')
                <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="my-7 flex items-center gap-4" aria-hidden="true">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black uppercase tracking-[.18em] text-slate-500">OR</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <div x-data="{ clearImage: false, imageUrl: @js(old('chart_image_url', $group->chart_image_url)), preview: @js($group->chartImageUrl()) }">
            <input type="hidden" name="chart_image_url" x-model="imageUrl">

            <label class="admin-label">Upload size chart image
                <input
                    class="admin-input py-3"
                    type="file"
                    name="chart_image"
                    x-ref="chartUpload"
                    accept="image/jpeg,image/png,image/webp,image/avif"
                    @change="const file=$event.target.files?.[0]; if(file){imageUrl=''; preview=URL.createObjectURL(file); clearImage=false}"
                >
                <span class="mt-2 block text-xs font-medium text-slate-500">JPG, PNG, WebP or AVIF. Maximum 5 MB.</span>
                @error('chart_image')
                    <span class="mt-2 block text-sm font-bold text-red-700">{{ $message }}</span>
                @enderror
                @error('chart_image_url')
                    <span class="mt-2 block text-sm font-bold text-red-700">{{ $message }}</span>
                @enderror
            </label>

            <input type="hidden" name="clear_chart_image" :value="clearImage ? 1 : 0">

            <div x-show="preview" x-cloak class="mt-5 flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <img :src="preview" alt="Size chart preview" class="h-24 w-24 rounded-xl border border-slate-200 bg-white object-contain">
                <div>
                    <p class="text-sm font-black text-brand-ink">Size chart image preview</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Saving an image will use the image instead of formatted size-chart content.</p>
                    <button type="button" class="mt-3 text-sm font-black text-red-700" @click="imageUrl=''; preview=''; clearImage=true; if ($refs.chartUpload) $refs.chartUpload.value=''">Remove image</button>
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:justify-end">
        <a class="btn btn-white" href="{{ route('admin.size-option-groups.index') }}">Cancel</a>
        <button class="btn btn-red">{{ $isEdit ? 'Update Size Group' : 'Create Size Group' }}</button>
    </div>
</div>
