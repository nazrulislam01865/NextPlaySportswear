<x-layouts.admin
    title="Shipping & Delivery Page"
    eyebrow="Storefront Content"
    subtitle="Manage every Delivery-tab text, link, SEO field and content icon. The approved section order and layout stay fixed."
    :storefront-url="route('shipping')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input min-h-28 py-3';
        $imageAccept = 'image/jpeg,image/png,image/webp,image/avif';
    @endphp

    <form method="POST" action="{{ route('admin.shipping-delivery-page.update') }}" enctype="multipart/form-data" class="mx-auto max-w-6xl space-y-6">
        @csrf
        @method('PUT')

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->has('shipping_delivery_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('shipping_delivery_page') }}</div>
        @elseif($errors->any())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again. Existing uploaded icons have not been removed.</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 1</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Order Information Hero</h2>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label">Eyebrow</label>
                    <input class="{{ $fieldClass }}" name="hero[eyebrow]" value="{{ old('hero.eyebrow', data_get($shippingDelivery, 'hero.eyebrow')) }}" maxlength="120" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label">Heading</label>
                    <input class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($shippingDelivery, 'hero.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label">Subtitle</label>
                    <textarea class="{{ $textareaClass }}" name="hero[subtitle]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('hero.subtitle', data_get($shippingDelivery, 'hero.subtitle')) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 2</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Order Information Tabs</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The five positions are fixed. Only Delivery is active in this phase; you can edit each visible label.</p>
            </div>
            <div class="grid gap-4 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-5">
                @foreach(data_get($shippingDelivery, 'tabs', []) as $index => $tab)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="tabs[{{ $index }}][id]" value="{{ data_get($tab, 'id') }}">
                        <label class="admin-label">Tab {{ $index + 1 }} label</label>
                        <input class="{{ $fieldClass }}" name="tabs[{{ $index }}][label]" value="{{ old("tabs.$index.label", data_get($tab, 'label')) }}" maxlength="120" required @disabled(! $canManageShippingDeliveryPage)>
                        @if(data_get($tab, 'id') === 'delivery')
                            <p class="mt-2 text-xs font-bold text-brand-red">Active Delivery tab</p>
                        @else
                            <p class="mt-2 text-xs font-semibold text-slate-500">Placeholder tab</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 3</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Delivery Introduction & Top Cards</h2>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="admin-label">Delivery heading</label>
                        <input class="{{ $fieldClass }}" name="delivery_intro[title]" value="{{ old('delivery_intro.title', data_get($shippingDelivery, 'delivery_intro.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                    <div>
                        <label class="admin-label">Delivery subtitle</label>
                        <textarea class="{{ $textareaClass }}" name="delivery_intro[subtitle]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('delivery_intro.subtitle', data_get($shippingDelivery, 'delivery_intro.subtitle')) }}</textarea>
                    </div>
                </div>
                <div class="grid gap-5 xl:grid-cols-2">
                    @foreach(data_get($shippingDelivery, 'info_cards.cards', []) as $index => $card)
                        @php($base = "info_cards.cards.$index")
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <input type="hidden" name="info_cards[cards][{{ $index }}][id]" value="{{ data_get($card, 'id') }}">
                            <div class="mb-4 flex min-h-20 items-center justify-center rounded-xl border border-slate-200 bg-white p-3">
                                <x-storefront.shipping-delivery.icon :icon-url="data_get($card, 'icon_url')" :fallback="data_get($card, 'fallback_icon')" :alt="data_get($card, 'icon_alt')" :size="44" />
                            </div>
                            <div class="space-y-4">
                                <div><label class="admin-label">Card title</label><input class="{{ $fieldClass }}" name="info_cards[cards][{{ $index }}][title]" value="{{ old($base.'.title', data_get($card, 'title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                                <div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="info_cards[cards][{{ $index }}][description]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old($base.'.description', data_get($card, 'description')) }}</textarea></div>
                                <div><label class="admin-label">Icon alt text</label><input class="{{ $fieldClass }}" name="info_cards[cards][{{ $index }}][icon_alt]" value="{{ old($base.'.icon_alt', data_get($card, 'icon_alt')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                                <div data-shipping-delivery-upload-slot>
                                    <label class="admin-label">Upload / replace icon</label>
                                    <input type="file" name="info_card_icon_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageShippingDeliveryPage)>
                                    <p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB.</p>
                                </div>
                                @if(data_get($card, 'has_custom_icon'))
                                    <label class="flex gap-3 text-sm font-bold text-slate-700"><input type="hidden" name="remove_info_card_icon_{{ $index }}" value="0"><input type="checkbox" name="remove_info_card_icon_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageShippingDeliveryPage)><span>Remove custom icon and restore the built-in icon</span></label>
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
                <h2 class="mt-2 text-2xl font-black text-brand-ink">How Delivery Works</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The three timeline positions remain fixed numbered circles, matching the prototype.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div><label class="admin-label">Section heading</label><input class="{{ $fieldClass }}" name="delivery_steps[title]" value="{{ old('delivery_steps.title', data_get($shippingDelivery, 'delivery_steps.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                <div class="grid gap-5 xl:grid-cols-3">
                    @foreach(data_get($shippingDelivery, 'delivery_steps.steps', []) as $index => $step)
                        @php($base = "delivery_steps.steps.$index")
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <input type="hidden" name="delivery_steps[steps][{{ $index }}][id]" value="{{ data_get($step, 'id') }}">
                            <div class="space-y-4">
                                <div><label class="admin-label">Step number</label><input class="{{ $fieldClass }}" name="delivery_steps[steps][{{ $index }}][number]" value="{{ old($base.'.number', data_get($step, 'number')) }}" maxlength="10" required @disabled(! $canManageShippingDeliveryPage)></div>
                                <div><label class="admin-label">Step title</label><input class="{{ $fieldClass }}" name="delivery_steps[steps][{{ $index }}][title]" value="{{ old($base.'.title', data_get($step, 'title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                                <div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="delivery_steps[steps][{{ $index }}][description]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old($base.'.description', data_get($step, 'description')) }}</textarea></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 5</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Delivery Notice</h2></div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="space-y-4">
                    <div><label class="admin-label">Notice text</label><textarea class="{{ $textareaClass }}" name="notice[text]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('notice.text', data_get($shippingDelivery, 'notice.text')) }}</textarea></div>
                    <div><label class="admin-label">Icon alt text</label><input class="{{ $fieldClass }}" name="notice[icon_alt]" value="{{ old('notice.icon_alt', data_get($shippingDelivery, 'notice.icon_alt')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                    <div data-shipping-delivery-upload-slot><label class="admin-label">Upload / replace icon</label><input type="file" name="notice_icon" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageShippingDeliveryPage)><p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB.</p></div>
                    @if(data_get($shippingDelivery, 'notice.has_custom_icon'))
                        <label class="flex gap-3 text-sm font-bold text-slate-700"><input type="hidden" name="remove_notice_icon" value="0"><input type="checkbox" name="remove_notice_icon" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageShippingDeliveryPage)><span>Remove custom icon and restore the built-in icon</span></label>
                    @endif
                </div>
                <div class="flex min-h-40 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-6"><x-storefront.shipping-delivery.icon :icon-url="data_get($shippingDelivery, 'notice.icon_url')" :fallback="data_get($shippingDelivery, 'notice.fallback_icon')" :alt="data_get($shippingDelivery, 'notice.icon_alt')" :size="56" /></div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 6</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Delivery Questions</h2></div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div><label class="admin-label">Heading</label><input class="{{ $fieldClass }}" name="faqs[title]" value="{{ old('faqs.title', data_get($shippingDelivery, 'faqs.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div>
                    <div><label class="admin-label">Subtitle</label><textarea class="{{ $textareaClass }}" name="faqs[subtitle]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('faqs.subtitle', data_get($shippingDelivery, 'faqs.subtitle')) }}</textarea></div>
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach(data_get($shippingDelivery, 'faqs.items', []) as $index => $faq)
                        @php($base = "faqs.items.$index")
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><input type="hidden" name="faqs[items][{{ $index }}][id]" value="{{ data_get($faq, 'id') }}"><div class="space-y-4"><div><label class="admin-label">Question {{ $index + 1 }}</label><input class="{{ $fieldClass }}" name="faqs[items][{{ $index }}][question]" value="{{ old($base.'.question', data_get($faq, 'question')) }}" maxlength="500" required @disabled(! $canManageShippingDeliveryPage)></div><div><label class="admin-label">Answer</label><textarea class="{{ $textareaClass }}" name="faqs[items][{{ $index }}][answer]" maxlength="2000" required @disabled(! $canManageShippingDeliveryPage)>{{ old($base.'.answer', data_get($faq, 'answer')) }}</textarea></div></div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 7</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Shipping Address Checklist</h2></div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2"><div><label class="admin-label">Heading</label><input class="{{ $fieldClass }}" name="address_checklist[title]" value="{{ old('address_checklist.title', data_get($shippingDelivery, 'address_checklist.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div><div><label class="admin-label">Subtitle</label><textarea class="{{ $textareaClass }}" name="address_checklist[subtitle]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('address_checklist.subtitle', data_get($shippingDelivery, 'address_checklist.subtitle')) }}</textarea></div></div>
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach(data_get($shippingDelivery, 'address_checklist.items', []) as $index => $item)
                        @php($base = "address_checklist.items.$index")
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><input type="hidden" name="address_checklist[items][{{ $index }}][id]" value="{{ data_get($item, 'id') }}"><div class="mb-4 flex min-h-20 items-center justify-center rounded-xl border border-slate-200 bg-white p-3"><x-storefront.shipping-delivery.icon :icon-url="data_get($item, 'icon_url')" :fallback="data_get($item, 'fallback_icon')" :alt="data_get($item, 'icon_alt')" :size="42" /></div><div class="space-y-4"><div><label class="admin-label">Item title</label><input class="{{ $fieldClass }}" name="address_checklist[items][{{ $index }}][title]" value="{{ old($base.'.title', data_get($item, 'title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div><div><label class="admin-label">Description</label><textarea class="{{ $textareaClass }}" name="address_checklist[items][{{ $index }}][description]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old($base.'.description', data_get($item, 'description')) }}</textarea></div><div><label class="admin-label">Icon alt text</label><input class="{{ $fieldClass }}" name="address_checklist[items][{{ $index }}][icon_alt]" value="{{ old($base.'.icon_alt', data_get($item, 'icon_alt')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div><div data-shipping-delivery-upload-slot><label class="admin-label">Upload / replace icon</label><input type="file" name="checklist_icon_{{ $index }}" accept="{{ $imageAccept }}" class="admin-input h-auto py-3" @disabled(! $canManageShippingDeliveryPage)><p class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, WebP or AVIF. Maximum 2 MB.</p></div>@if(data_get($item, 'has_custom_icon'))<label class="flex gap-3 text-sm font-bold text-slate-700"><input type="hidden" name="remove_checklist_icon_{{ $index }}" value="0"><input type="checkbox" name="remove_checklist_icon_{{ $index }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageShippingDeliveryPage)><span>Remove custom icon and restore the built-in icon</span></label>@endif</div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 8</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Order Status CTA</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                @foreach([
                    ['cta[title]', 'cta.title', 'Heading', 255],
                    ['cta[description]', 'cta.description', 'Description', 1000],
                    ['cta[primary_label]', 'cta.primary_label', 'Track button label', 100],
                    ['cta[primary_url]', 'cta.primary_url', 'Track button destination', 2048],
                    ['cta[policy_label]', 'cta.policy_label', 'Policy link label', 100],
                    ['cta[policy_url]', 'cta.policy_url', 'Policy link destination', 2048],
                ] as [$name, $oldKey, $label, $max])
                    <div><label class="admin-label">{{ $label }}</label><input class="{{ $fieldClass }}" name="{{ $name }}" value="{{ old($oldKey, data_get($shippingDelivery, $oldKey)) }}" maxlength="{{ $max }}" required @disabled(! $canManageShippingDeliveryPage)></div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">SEO</p><h2 class="mt-2 text-2xl font-black text-brand-ink">Search & Sharing</h2></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2"><div><label class="admin-label">SEO title</label><input class="{{ $fieldClass }}" name="seo[title]" value="{{ old('seo.title', data_get($shippingDelivery, 'seo.title')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)></div><div><label class="admin-label">Meta description</label><textarea class="{{ $textareaClass }}" name="seo[description]" maxlength="500" required @disabled(! $canManageShippingDeliveryPage)>{{ old('seo.description', data_get($shippingDelivery, 'seo.description')) }}</textarea></div></div>
        </section>

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
            <a href="{{ route('shipping') }}" target="_blank" rel="noopener" class="btn btn-secondary">Preview Shipping & Delivery</a>
            <button type="submit" class="btn btn-primary" @disabled(! $canManageShippingDeliveryPage)>Save Shipping & Delivery Page</button>
        </div>
    </form>
</x-layouts.admin>
