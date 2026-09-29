<x-layouts.admin
    title="About Page"
    eyebrow="Storefront Content"
    subtitle="Manage every About NextPlay text, link, image and icon. The approved section order and storefront layout stay fixed."
    :storefront-url="route('about')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input min-h-28 py-3';
        $imageAccept = 'image/jpeg,image/png,image/webp,image/avif';
    @endphp

    <form method="POST" action="{{ route('admin.about-page.update') }}" enctype="multipart/form-data" class="mx-auto max-w-6xl space-y-6">
        @csrf
        @method('PUT')

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('about_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('about_page') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('about_page'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted About page fields and save again. Existing uploaded media has not been removed.</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 1</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Hero</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The dark About NextPlay banner at the top of the page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="about-hero-eyebrow">Eyebrow</label>
                    <input id="about-hero-eyebrow" class="{{ $fieldClass }}" name="hero[eyebrow]" value="{{ old('hero.eyebrow', data_get($about, 'hero.eyebrow')) }}" maxlength="120" required @disabled(! $canManageAboutPage)>
                    @error('hero.eyebrow')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="about-hero-title">Heading</label>
                    <input id="about-hero-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($about, 'hero.title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                    @error('hero.title')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="about-hero-description">Description</label>
                    <textarea id="about-hero-description" class="{{ $textareaClass }}" name="hero[description]" maxlength="1000" required @disabled(! $canManageAboutPage)>{{ old('hero.description', data_get($about, 'hero.description')) }}</textarea>
                    @error('hero.description')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 2</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Introduction</h2>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-5">
                    <div>
                        <label class="admin-label" for="about-intro-title">Heading</label>
                        <textarea id="about-intro-title" class="{{ $textareaClass }}" name="introduction[title]" maxlength="255" required @disabled(! $canManageAboutPage)>{{ old('introduction.title', data_get($about, 'introduction.title')) }}</textarea>
                        @error('introduction.title')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label" for="about-intro-description">Description</label>
                        <textarea id="about-intro-description" class="{{ $textareaClass }}" name="introduction[description]" maxlength="1000" required @disabled(! $canManageAboutPage)>{{ old('introduction.description', data_get($about, 'introduction.description')) }}</textarea>
                        @error('introduction.description')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label" for="about-intro-alt">Image alt text</label>
                        <input id="about-intro-alt" class="{{ $fieldClass }}" name="introduction[image_alt]" value="{{ old('introduction.image_alt', data_get($about, 'introduction.image_alt')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                        @error('introduction.image_alt')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label" for="about-intro-image">Upload / replace image</label>
                        <input id="about-intro-image" type="file" name="introduction_image" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageAboutPage)>
                        <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 10 MB.</p>
                        @error('introduction_image')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if(data_get($about, 'introduction.has_custom_image'))
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                            <input type="hidden" name="remove_introduction_image" value="0">
                            <input type="checkbox" name="remove_introduction_image" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked(old('remove_introduction_image')) @disabled(! $canManageAboutPage)>
                            <span><span class="block text-brand-ink">Remove custom introduction image</span><span class="mt-1 block text-xs font-semibold text-slate-500">The shipped prototype image will be restored.</span></span>
                        </label>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Current image</p>
                    <img src="{{ data_get($about, 'introduction.image_url') }}" alt="{{ data_get($about, 'introduction.image_alt') }}" class="mt-4 aspect-[2/1] w-full object-cover">
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 3</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">What We Do</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The three service positions are fixed to protect the prototype layout.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="about-services-title">Section heading</label>
                    <input id="about-services-title" class="{{ $fieldClass }}" name="what_we_do[title]" value="{{ old('what_we_do.title', data_get($about, 'what_we_do.title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                </div>
                <div class="grid gap-5 xl:grid-cols-3">
                    @foreach(data_get($about, 'what_we_do.cards', []) as $index => $card)
                        @php($base = "what_we_do.cards.$index")
                        <article data-about-service-editor class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <input type="hidden" name="what_we_do[cards][{{ $index }}][id]" value="{{ data_get($card, 'id') }}">
                            <div class="mb-4 flex min-h-20 items-center justify-center rounded-xl border border-slate-200 bg-white p-3">
                                @if(data_get($card, 'icon_url'))
                                    <img src="{{ data_get($card, 'icon_url') }}" alt="{{ data_get($card, 'icon_alt') }}" class="h-14 w-14 object-contain">
                                @else
                                    <x-storefront.about.icon :name="data_get($card, 'fallback_icon')" :size="54" />
                                @endif
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="admin-label">Card title</label>
                                    <input class="{{ $fieldClass }}" name="what_we_do[cards][{{ $index }}][title]" value="{{ old($base.'.title', data_get($card, 'title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                                </div>
                                <div>
                                    <label class="admin-label">Description</label>
                                    <textarea class="{{ $textareaClass }}" name="what_we_do[cards][{{ $index }}][description]" maxlength="1000" required @disabled(! $canManageAboutPage)>{{ old($base.'.description', data_get($card, 'description')) }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label">Icon alt text</label>
                                    <input class="{{ $fieldClass }}" name="what_we_do[cards][{{ $index }}][icon_alt]" value="{{ old($base.'.icon_alt', data_get($card, 'icon_alt')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                                </div>
                                <div>
                                    <label class="admin-label">Upload / replace icon</label>
                                    <input type="file" name="service_icon_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageAboutPage)>
                                    <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB. Transparent PNG/WebP works well.</p>
                                    @error('service_icon_'.$index)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                                </div>
                                @if(data_get($card, 'has_custom_icon'))
                                    <label class="flex gap-3 text-sm font-bold text-slate-700">
                                        <input type="hidden" name="remove_service_icon_{{ $index }}" value="0">
                                        <input type="checkbox" name="remove_service_icon_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageAboutPage)>
                                        <span>Remove custom icon and use the built-in fallback</span>
                                    </label>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 4</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">How We Work</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The four process positions and connecting arrows remain fixed; all visible step content is editable.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="about-process-title-admin">Section heading</label>
                    <input id="about-process-title-admin" class="{{ $fieldClass }}" name="how_we_work[title]" value="{{ old('how_we_work.title', data_get($about, 'how_we_work.title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                </div>
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach(data_get($about, 'how_we_work.steps', []) as $index => $step)
                        @php($base = "how_we_work.steps.$index")
                        <article data-about-process-editor class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <input type="hidden" name="how_we_work[steps][{{ $index }}][id]" value="{{ data_get($step, 'id') }}">
                            <div class="mb-4 flex min-h-20 items-center justify-center rounded-xl border border-slate-200 bg-white p-3">
                                @if(data_get($step, 'icon_url'))
                                    <img src="{{ data_get($step, 'icon_url') }}" alt="{{ data_get($step, 'icon_alt') }}" class="h-12 w-12 object-contain">
                                @else
                                    <x-storefront.about.icon :name="data_get($step, 'fallback_icon')" :size="42" />
                                @endif
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="admin-label">Step number</label>
                                    <input class="{{ $fieldClass }}" name="how_we_work[steps][{{ $index }}][number]" value="{{ old($base.'.number', data_get($step, 'number')) }}" maxlength="10" required @disabled(! $canManageAboutPage)>
                                </div>
                                <div>
                                    <label class="admin-label">Step title</label>
                                    <input class="{{ $fieldClass }}" name="how_we_work[steps][{{ $index }}][title]" value="{{ old($base.'.title', data_get($step, 'title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                                </div>
                                <div>
                                    <label class="admin-label">Description</label>
                                    <textarea class="{{ $textareaClass }}" name="how_we_work[steps][{{ $index }}][description]" maxlength="1000" required @disabled(! $canManageAboutPage)>{{ old($base.'.description', data_get($step, 'description')) }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label">Icon alt text</label>
                                    <input class="{{ $fieldClass }}" name="how_we_work[steps][{{ $index }}][icon_alt]" value="{{ old($base.'.icon_alt', data_get($step, 'icon_alt')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                                </div>
                                <div>
                                    <label class="admin-label">Upload / replace icon</label>
                                    <input type="file" name="process_icon_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageAboutPage)>
                                    <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB.</p>
                                    @error('process_icon_'.$index)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                                </div>
                                @if(data_get($step, 'has_custom_icon'))
                                    <label class="flex gap-3 text-sm font-bold text-slate-700">
                                        <input type="hidden" name="remove_process_icon_{{ $index }}" value="0">
                                        <input type="checkbox" name="remove_process_icon_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageAboutPage)>
                                        <span>Remove custom icon and use the built-in fallback</span>
                                    </label>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 5</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Gallery</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Four fixed image positions matching the approved storefront composition.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach(data_get($about, 'gallery.items', []) as $index => $item)
                    @php($base = "gallery.items.$index")
                    <article data-about-gallery-editor class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="gallery[items][{{ $index }}][id]" value="{{ data_get($item, 'id') }}">
                        <img src="{{ data_get($item, 'image_url') }}" alt="{{ data_get($item, 'image_alt') }}" class="aspect-[4/3] w-full object-cover">
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="admin-label">Image alt text</label>
                                <input class="{{ $fieldClass }}" name="gallery[items][{{ $index }}][image_alt]" value="{{ old($base.'.image_alt', data_get($item, 'image_alt')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                            </div>
                            <div>
                                <label class="admin-label">Upload / replace image</label>
                                <input type="file" name="gallery_image_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageAboutPage)>
                                <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 10 MB.</p>
                                @error('gallery_image_'.$index)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            @if(data_get($item, 'has_custom_image'))
                                <label class="flex gap-3 text-sm font-bold text-slate-700">
                                    <input type="hidden" name="remove_gallery_image_{{ $index }}" value="0">
                                    <input type="checkbox" name="remove_gallery_image_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageAboutPage)>
                                    <span>Remove custom image and restore default</span>
                                </label>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 6</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Made for Your Team</h2>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                @foreach([
                    ['cta[eyebrow]', 'cta.eyebrow', 'Eyebrow', 120],
                    ['cta[title]', 'cta.title', 'Heading', 255],
                    ['cta[primary_label]', 'cta.primary_label', 'Primary button label', 100],
                    ['cta[primary_url]', 'cta.primary_url', 'Primary button URL', 2048],
                    ['cta[secondary_label]', 'cta.secondary_label', 'Secondary button label', 100],
                    ['cta[secondary_url]', 'cta.secondary_url', 'Secondary button URL', 2048],
                ] as [$name, $key, $label, $max])
                    <div>
                        <label class="admin-label">{{ $label }}</label>
                        <input class="{{ $fieldClass }}" name="{{ $name }}" value="{{ old($key, data_get($about, $key)) }}" maxlength="{{ $max }}" required @disabled(! $canManageAboutPage)>
                        @error($key)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 7</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Need Help</h2>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="admin-label">Heading</label>
                        <input class="{{ $fieldClass }}" name="help[title]" value="{{ old('help.title', data_get($about, 'help.title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                    </div>
                    <div>
                        <label class="admin-label">Button label</label>
                        <input class="{{ $fieldClass }}" name="help[button_label]" value="{{ old('help.button_label', data_get($about, 'help.button_label')) }}" maxlength="100" required @disabled(! $canManageAboutPage)>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Description</label>
                        <textarea class="{{ $textareaClass }}" name="help[description]" maxlength="1000" required @disabled(! $canManageAboutPage)>{{ old('help.description', data_get($about, 'help.description')) }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Button URL</label>
                        <input class="{{ $fieldClass }}" name="help[button_url]" value="{{ old('help.button_url', data_get($about, 'help.button_url')) }}" maxlength="2048" required @disabled(! $canManageAboutPage)>
                    </div>
                    <div>
                        <label class="admin-label">Icon alt text</label>
                        <input class="{{ $fieldClass }}" name="help[icon_alt]" value="{{ old('help.icon_alt', data_get($about, 'help.icon_alt')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Upload / replace help icon</label>
                        <input type="file" name="help_icon" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageAboutPage)>
                        <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB.</p>
                        @error('help_icon')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if(data_get($about, 'help.has_custom_icon'))
                        <label class="lg:col-span-2 flex gap-3 text-sm font-bold text-slate-700">
                            <input type="hidden" name="remove_help_icon" value="0">
                            <input type="checkbox" name="remove_help_icon" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageAboutPage)>
                            <span>Remove custom help icon and use the built-in headset icon</span>
                        </label>
                    @endif
                </div>
                <div class="flex min-h-48 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    @if(data_get($about, 'help.icon_url'))
                        <img src="{{ data_get($about, 'help.icon_url') }}" alt="{{ data_get($about, 'help.icon_alt') }}" class="h-20 w-20 object-contain">
                    @else
                        <x-storefront.about.icon :name="data_get($about, 'help.fallback_icon')" :size="64" />
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Search & sharing</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">SEO</h2>
            </div>
            <div class="grid gap-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label">SEO title</label>
                    <input class="{{ $fieldClass }}" name="seo[title]" value="{{ old('seo.title', data_get($about, 'seo.title')) }}" maxlength="255" required @disabled(! $canManageAboutPage)>
                </div>
                <div>
                    <label class="admin-label">SEO description</label>
                    <textarea class="{{ $textareaClass }}" name="seo[description]" maxlength="500" required @disabled(! $canManageAboutPage)>{{ old('seo.description', data_get($about, 'seo.description')) }}</textarea>
                </div>
            </div>
        </section>

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <p class="text-xs font-semibold text-slate-500">Section order, card counts, process arrows and gallery positions are protected by the storefront code.</p>
            <div class="flex gap-3">
                <a href="{{ route('about') }}" target="_blank" rel="noopener" class="btn btn-white">Preview About Page</a>
                @if($canManageAboutPage)
                    <button type="submit" class="btn btn-primary">Save About Page</button>
                @endif
            </div>
        </div>
    </form>
</x-layouts.admin>
