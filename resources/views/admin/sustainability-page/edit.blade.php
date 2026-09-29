<x-layouts.admin title="Sustainability Page">
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input min-h-28 resize-y';
        $imageAccept = '.jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Store</p>
            <h1 class="mt-2 text-3xl font-black text-brand-ink">Sustainability Page</h1>
            <p class="mt-2 max-w-3xl text-sm font-semibold text-slate-500">Edit the content inside the fixed Sustainability prototype. Section order, feature-row direction and card count remain protected.</p>
        </div>
        <a href="{{ route('sustainability') }}" target="_blank" rel="noopener" class="btn btn-secondary">Preview Sustainability</a>
    </div>

    @if(session('status'))<div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-800"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.sustainability-page.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 1</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Hero</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-3">
                <div><label class="admin-label">Eyebrow</label><input class="{{ $fieldClass }}" name="hero[eyebrow]" value="{{ old('hero.eyebrow', data_get($sustainability,'hero.eyebrow')) }}" maxlength="120" required @disabled(! $canManageSustainabilityPage)></div>
                <div><label class="admin-label">Title</label><input class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($sustainability,'hero.title')) }}" maxlength="255" required @disabled(! $canManageSustainabilityPage)></div>
                <div><label class="admin-label">Subtitle</label><textarea class="{{ $textareaClass }}" name="hero[subtitle]" maxlength="1000" required @disabled(! $canManageSustainabilityPage)>{{ old('hero.subtitle', data_get($sustainability,'hero.subtitle')) }}</textarea></div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 2</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Our Approach</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2"><div><label class="admin-label">Heading</label><input class="{{ $fieldClass }}" name="approach[title]" value="{{ old('approach.title',data_get($sustainability,'approach.title')) }}" required @disabled(! $canManageSustainabilityPage)></div><div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="approach[description]" maxlength="2000" required @disabled(! $canManageSustainabilityPage)>{{ old('approach.description',data_get($sustainability,'approach.description')) }}</textarea></div></div>
        </section>

        @foreach(data_get($sustainability,'features',[]) as $index => $feature)
            @php($featureName = ['Materials & Waste','People & Partners','Packaging & Delivery'][$index] ?? 'Feature')
            <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Feature {{ $index + 1 }}</p><h2 class="mt-2 text-2xl font-black text-brand-ink">{{ $featureName }}</h2></div>
                <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="space-y-4">
                        <input type="hidden" name="features[{{ $index }}][id]" value="{{ data_get($feature,'id') }}">
                        <div><label class="admin-label">Heading</label><input class="{{ $fieldClass }}" name="features[{{ $index }}][heading]" value="{{ old('features.'.$index.'.heading',data_get($feature,'heading')) }}" maxlength="255" required @disabled(! $canManageSustainabilityPage)></div>
                        <div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="features[{{ $index }}][description]" maxlength="1500" required @disabled(! $canManageSustainabilityPage)>{{ old('features.'.$index.'.description',data_get($feature,'description')) }}</textarea></div>
                        <div><label class="admin-label">Image alt text</label><input class="{{ $fieldClass }}" name="features[{{ $index }}][image_alt]" value="{{ old('features.'.$index.'.image_alt',data_get($feature,'image_alt')) }}" maxlength="255" required @disabled(! $canManageSustainabilityPage)></div>
                        <div data-sustainability-upload-slot><label class="admin-label">Upload / replace image</label><input type="file" name="feature_image_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageSustainabilityPage)><p class="mt-2 text-xs font-semibold text-slate-500">JPG, JPEG, PNG, WebP or AVIF. Maximum 2 MB.</p></div>
                        @if(data_get($feature,'has_custom_image'))<label class="flex gap-3 text-sm font-bold text-slate-700"><input type="hidden" name="remove_feature_image_{{ $index }}" value="0"><input type="checkbox" name="remove_feature_image_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageSustainabilityPage)><span>Remove custom image and restore the prototype image</span></label>@endif
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"><img src="{{ data_get($feature,'image_url') }}" alt="{{ data_get($feature,'image_alt') }}" class="h-full min-h-52 w-full object-cover"></div>
                </div>
            </section>
        @endforeach

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 6</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Keeping You Informed</h2></div>
            <div class="space-y-6 p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2"><div><label class="admin-label">Heading</label><input class="{{ $fieldClass }}" name="informed[title]" value="{{ old('informed.title',data_get($sustainability,'informed.title')) }}" required @disabled(! $canManageSustainabilityPage)></div><div><label class="admin-label">Subtitle</label><textarea class="{{ $textareaClass }}" name="informed[subtitle]" required @disabled(! $canManageSustainabilityPage)>{{ old('informed.subtitle',data_get($sustainability,'informed.subtitle')) }}</textarea></div></div>
                <div class="grid gap-5 lg:grid-cols-3">
                    @foreach(data_get($sustainability,'informed.cards',[]) as $index => $card)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <input type="hidden" name="informed[cards][{{ $index }}][id]" value="{{ data_get($card,'id') }}">
                            <div class="mb-4 flex min-h-28 items-center justify-center rounded-xl border border-slate-200 bg-white p-4"><x-storefront.sustainability.icon :icon-url="data_get($card,'icon_url')" :fallback="data_get($card,'fallback_icon')" :alt="data_get($card,'icon_alt')" :size="52" /></div>
                            <div class="space-y-4"><div><label class="admin-label">Card title</label><input class="{{ $fieldClass }}" name="informed[cards][{{ $index }}][title]" value="{{ old('informed.cards.'.$index.'.title',data_get($card,'title')) }}" required @disabled(! $canManageSustainabilityPage)></div><div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="informed[cards][{{ $index }}][description]" required @disabled(! $canManageSustainabilityPage)>{{ old('informed.cards.'.$index.'.description',data_get($card,'description')) }}</textarea></div><div><label class="admin-label">Icon alt text</label><input class="{{ $fieldClass }}" name="informed[cards][{{ $index }}][icon_alt]" value="{{ old('informed.cards.'.$index.'.icon_alt',data_get($card,'icon_alt')) }}" required @disabled(! $canManageSustainabilityPage)></div><div data-sustainability-upload-slot><label class="admin-label">Upload / replace icon</label><input type="file" name="info_card_icon_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageSustainabilityPage)><p class="mt-2 text-xs font-semibold text-slate-500">JPG, JPEG, PNG, WebP or AVIF. Maximum 2 MB.</p></div>@if(data_get($card,'has_custom_icon'))<label class="flex gap-3 text-sm font-bold text-slate-700"><input type="hidden" name="remove_info_card_icon_{{ $index }}" value="0"><input type="checkbox" name="remove_info_card_icon_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageSustainabilityPage)><span>Remove custom icon and restore the built-in icon</span></label>@endif</div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 7</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Bottom CTA</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                @foreach([['cta[title]','cta.title','Heading',255],['cta[description]','cta.description','Description',1000],['cta[primary_label]','cta.primary_label','Primary button label',100],['cta[primary_url]','cta.primary_url','Primary button destination',2048],['cta[secondary_label]','cta.secondary_label','Secondary button label',100],['cta[secondary_url]','cta.secondary_url','Secondary button destination',2048]] as [$name,$oldKey,$label,$max])<div><label class="admin-label">{{ $label }}</label><input class="{{ $fieldClass }}" name="{{ $name }}" value="{{ old($oldKey,data_get($sustainability,$oldKey)) }}" maxlength="{{ $max }}" required @disabled(! $canManageSustainabilityPage)></div>@endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">SEO</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Search & Sharing</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2"><div><label class="admin-label">SEO title</label><input class="{{ $fieldClass }}" name="seo[title]" value="{{ old('seo.title',data_get($sustainability,'seo.title')) }}" maxlength="255" required @disabled(! $canManageSustainabilityPage)></div><div><label class="admin-label">Meta description</label><textarea class="{{ $textareaClass }}" name="seo[description]" maxlength="500" required @disabled(! $canManageSustainabilityPage)>{{ old('seo.description',data_get($sustainability,'seo.description')) }}</textarea></div></div>
        </section>

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur"><a href="{{ route('sustainability') }}" target="_blank" rel="noopener" class="btn btn-secondary">Preview Sustainability</a><button type="submit" class="btn btn-primary" @disabled(! $canManageSustainabilityPage)>Save Sustainability Page</button></div>
    </form>
</x-layouts.admin>
