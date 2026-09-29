@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-sale-campaign.css') }}?v={{ file_exists(public_path('css/admin-sale-campaign.css')) ? filemtime(public_path('css/admin-sale-campaign.css')) : 1 }}">
@endpush
@push('admin-page-scripts')
    <script src="{{ asset('js/admin-sale-campaign.js') }}?v={{ file_exists(public_path('js/admin-sale-campaign.js')) ? filemtime(public_path('js/admin-sale-campaign.js')) : 1 }}"></script>
@endpush

@php
    $isEditingCampaign = isset($editingCampaign) && $editingCampaign;
    $campaignFormAction = $isEditingCampaign
        ? route('admin.promotions.sales.update', $editingCampaign)
        : route('admin.promotions.sales.store');
@endphp

<x-layouts.admin
    :title="$isEditingCampaign ? 'Edit Sale Campaign' : 'Create Sale Campaign'"
    eyebrow="Promotions / Sales"
    :subtitle="$isEditingCampaign ? 'Update this sale campaign without affecting other campaigns.' : 'Set up a sale campaign to offer discounts on selected products and drive more sales.'"
>
    <div x-data="adminSaleCampaignFormV2(@js($initialCampaign))">
        <div class="np-sale-layout">
            <form
                id="sale-campaign-form"
                method="POST"
                action="{{ $campaignFormAction }}"
                enctype="multipart/form-data"
                class="space-y-6"
            >
                @csrf
                @if($isEditingCampaign)
                    @method('PUT')
                @endif

                <x-admin.section-card title="1. Basics" description="Basic information about this sale campaign.">
                    <div class="grid gap-5 lg:grid-cols-3">
                        <label class="admin-label">
                            Campaign name <span class="text-brand-red">*</span>
                            <input type="text" name="campaign_name" x-model="campaignName" class="admin-input" maxlength="255" required autocomplete="off" placeholder="Autumn Teamwear Sale">
                        </label>

                        <label class="admin-label">
                            Internal code
                            <input
                                type="text"
                                class="admin-input uppercase bg-slate-50 text-slate-600"
                                value="{{ $editingCampaign?->internal_code ?: 'Generated automatically when saved' }}"
                                readonly
                                aria-readonly="true"
                            >
                            <small class="mt-1 block font-normal text-slate-500">Generated automatically from the campaign name and kept stable after saving.</small>
                        </label>

                        <label class="admin-label">
                            Status
                            <select name="status" x-model="status" class="admin-input">
                                <option value="draft">Draft</option>
                                <option value="live">Live</option>
                            </select>
                        </label>
                    </div>
                </x-admin.section-card>

                <x-admin.section-card title="2. Discount" description="Set the discount value for this campaign.">
                    <input type="hidden" name="discount_type" :value="discountType">

                    <div class="np-sale-discount-layout">
                        <div>
                            <div class="grid overflow-hidden rounded-xl border border-slate-300 sm:grid-cols-2" role="group" aria-label="Discount type">
                                <button
                                    type="button"
                                    class="flex min-h-12 items-center justify-center gap-2 border-b border-slate-300 px-4 text-sm font-bold transition sm:border-b-0 sm:border-r"
                                    :class="discountType === 'percentage' ? 'bg-brand-dark text-white' : 'bg-white text-brand-ink hover:bg-slate-50'"
                                    @click="discountType = 'percentage'"
                                ><span class="text-lg">%</span><span>Percentage discount</span></button>
                                <button
                                    type="button"
                                    class="flex min-h-12 items-center justify-center gap-2 px-4 text-sm font-bold transition"
                                    :class="discountType === 'fixed' ? 'bg-brand-dark text-white' : 'bg-white text-brand-ink hover:bg-slate-50'"
                                    @click="discountType = 'fixed'"
                                ><span class="text-lg">£</span><span>Fixed amount discount</span></button>
                            </div>

                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <label class="admin-label">
                                    Discount value <span class="text-brand-red">*</span>
                                    <div class="np-sale-number-control">
                                        <span x-show="discountType === 'fixed'" x-cloak class="np-sale-number-addon np-sale-number-addon--prefix">£</span>
                                        <input
                                            type="number"
                                            name="discount_value"
                                            min="0.01"
                                            :max="discountType === 'percentage' ? 100 : null"
                                            step="0.01"
                                            inputmode="decimal"
                                            x-model.number="discountValue"
                                            class="np-sale-number-input"
                                            required
                                        >
                                        <span x-show="discountType === 'percentage'" class="np-sale-number-addon np-sale-number-addon--suffix">%</span>
                                    </div>
                                </label>

                                <label class="admin-label">
                                    Maximum discount <span class="text-xs font-semibold text-slate-400">(optional)</span>
                                    <div class="np-sale-number-control">
                                        <span class="np-sale-number-addon np-sale-number-addon--prefix">£</span>
                                        <input type="number" name="maximum_discount" min="0.01" step="0.01" inputmode="decimal" x-model="maximumDiscount" class="np-sale-number-input" placeholder="20.00">
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-black text-brand-ink">Pricing preview</p>
                                <span class="text-xs font-semibold text-slate-400" x-show="previewProduct" x-text="previewProduct ? previewProduct.name : ''"></span>
                            </div>

                            <div x-show="!previewProduct" class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm font-medium leading-6 text-slate-500">
                                Select a specific product to preview its sale price.
                            </div>

                            <dl x-show="previewProduct" x-cloak class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Original price</dt><dd class="font-bold text-brand-ink" x-text="money(previewPrice, previewProduct)"></dd></div>
                                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Discount</dt><dd class="font-bold text-brand-red" x-text="`- ${money(previewDiscount, previewProduct)}`"></dd></div>
                                <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-3"><dt class="font-black text-brand-ink">Sale price</dt><dd class="text-lg font-black text-brand-ink" x-text="money(previewSalePrice, previewProduct)"></dd></div>
                            </dl>
                        </div>
                    </div>
                </x-admin.section-card>

                <x-admin.section-card title="3. Schedule" description="Set when this campaign will be active.">
                    <div class="np-sale-schedule-fields">
                        <label class="admin-label">
                            Start date &amp; time <span class="text-brand-red">*</span>
                            <div class="np-sale-inline-date-time">
                                <input type="date" name="start_date" x-model="startDate" class="admin-input" required>
                                <input type="time" name="start_time" x-model="startTime" class="admin-input" required>
                            </div>
                        </label>

                        <label class="admin-label">
                            End date &amp; time <span class="text-brand-red">*</span>
                            <div class="np-sale-inline-date-time">
                                <input type="date" name="end_date" x-model="endDate" class="admin-input" :min="startDate" required>
                                <input type="time" name="end_time" x-model="endTime" class="admin-input" required>
                            </div>
                        </label>

                        <label class="admin-label">
                            Time zone <span class="text-brand-red">*</span>
                            <select name="timezone" x-model="timezone" class="admin-input" required @disabled($timeZones->isEmpty())>
                                @if($timeZones->isEmpty())
                                    <option value="">No active time zones available</option>
                                @else
                                    @foreach($timeZones as $timeZone)
                                        <option value="{{ $timeZone->identifier }}">{{ $timeZone->label }} — {{ $timeZone->identifier }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </label>
                    </div>

                    <input type="hidden" name="repeat_weekdays" :value="repeatWeekdays ? 1 : 0">
                    <template x-for="day in weekdays" :key="`weekday-input-${day}`">
                        <input type="hidden" name="weekdays[]" :value="day">
                    </template>

                    <div class="np-sale-weekday-repeat-row">
                        <div class="np-sale-weekday-selector">
                            <p class="np-sale-weekday-heading">Active on weekdays</p>
                            <div class="np-sale-weekday-grid">
                                <template x-for="day in allWeekdays" :key="day">
                                    <button
                                        type="button"
                                        class="np-sale-weekday-button"
                                        :class="weekdays.includes(day) ? 'is-selected' : ''"
                                        :aria-pressed="weekdays.includes(day) ? 'true' : 'false'"
                                        @click="toggleWeekday(day)"
                                        x-text="day"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <label class="np-sale-repeat-inline">
                            <span class="np-sale-switch" aria-hidden="true">
                                <input type="checkbox" x-model="repeatWeekdays" class="sr-only">
                                <span class="np-sale-switch__track" :class="repeatWeekdays ? 'is-active' : ''">
                                    <span class="np-sale-switch__thumb"></span>
                                </span>
                            </span>
                            <span class="min-w-0">
                                <strong class="block text-sm font-black text-brand-ink">Repeat on selected weekdays</strong>
                                <small class="mt-1 block text-xs font-medium leading-5 text-slate-500">Campaign will run every week on the selected days within the date range.</small>
                            </span>
                        </label>
                    </div>
                </x-admin.section-card>

                <x-admin.section-card title="4. Applies to" description="Choose which products this campaign will apply to.">
                    <fieldset>
                        <legend class="sr-only">Campaign applies to</legend>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                            <template x-for="option in applyOptions" :key="option.value">
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-3 text-sm font-bold transition" :class="appliesTo === option.value ? 'border-brand-blue bg-blue-50 text-brand-blue' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" name="applies_to" :value="option.value" x-model="appliesTo" @change="resetTargetSelection()" class="h-4 w-4 border-slate-300 text-brand-blue">
                                    <span x-text="option.label"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>

                    <template x-for="target in selectedTargets" :key="`target-input-${target.id}`">
                        <input type="hidden" name="target_ids[]" :value="target.id">
                    </template>

                    <div x-show="appliesTo === 'all'" x-cloak class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600">
                        This campaign will apply to all eligible products. Use exclusions below if needed.
                    </div>

                    <div x-show="appliesTo !== 'all'" x-cloak class="np-sale-target-layout mt-5">
                        <div class="relative" @click.outside="targetPickerOpen = false">
                            <label class="admin-label">
                                <span x-text="targetFieldLabel"></span>
                                <span class="text-brand-red">*</span>
                                <input
                                    type="search"
                                    x-model="targetSearch"
                                    :placeholder="targetSearchPlaceholder"
                                    @focus="searchTargets()"
                                    @input.debounce.250ms="searchTargets()"
                                    class="admin-input"
                                    autocomplete="off"
                                >
                            </label>

                            <div x-show="targetPickerOpen" x-cloak class="absolute left-0 right-0 top-full z-40 mt-2 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                <p x-show="targetLoading" class="px-3 py-3 text-sm font-semibold text-slate-500">Searching...</p>
                                <p x-show="!targetLoading && targetResults.length === 0" class="px-3 py-3 text-sm font-semibold text-slate-500">No matching items found.</p>
                                <template x-for="option in targetResults" :key="`target-result-${option.id}`">
                                    <button type="button" @click="addTarget(option)" class="block w-full rounded-lg px-3 py-2.5 text-left hover:bg-slate-50">
                                        <strong class="block text-sm font-black text-brand-ink" x-text="option.name"></strong>
                                        <span class="mt-1 block truncate text-xs font-medium text-slate-500" x-text="option.path"></span>
                                    </button>
                                </template>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <template x-for="target in selectedTargets" :key="`target-chip-${target.id}`">
                                    <span class="inline-flex max-w-full items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                                        <span class="truncate" x-text="target.name"></span>
                                        <button type="button" @click="removeTarget(target.id)" class="text-base leading-none text-slate-400 hover:text-red-600" aria-label="Remove selection">×</button>
                                    </span>
                                </template>
                            </div>
                            <p class="mt-2 text-xs font-semibold text-slate-500" x-text="targetSelectionSummary"></p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-black text-brand-ink">Category structure</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">Selected items are shown with their full path.</p>
                            <div class="mt-3 space-y-2">
                                <p x-show="selectedTargets.length === 0" class="text-sm font-medium text-slate-500">No items selected yet.</p>
                                <template x-for="target in selectedTargets" :key="`structure-${target.id}`">
                                    <div class="rounded-lg bg-white px-3 py-2 text-xs font-semibold leading-5 text-slate-600" x-text="target.path || target.name"></div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <template x-for="product in excludedProducts" :key="`exclude-input-${product.id}`">
                        <input type="hidden" name="excluded_product_ids[]" :value="product.id">
                    </template>

                    <div x-ref="excludePickerRoot" class="relative mt-5 border-t border-slate-100 pt-5" @click.outside="closeExcludePicker()">
                        <label class="admin-label max-w-2xl">
                            Exclude products <span class="text-xs font-semibold text-slate-400">(optional)</span>
                            <input
                                type="search"
                                x-model="excludeSearch"
                                placeholder="Search and select products to exclude..."
                                @focus="searchExcludedProducts()"
                                @input.debounce.250ms="searchExcludedProducts()"
                                @blur="handleExcludeBlur($event)"
                                @keydown.escape.stop.prevent="closeExcludePicker()"
                                class="admin-input"
                                autocomplete="off"
                            >
                        </label>

                        <div x-show="excludePickerOpen" x-cloak class="absolute left-0 z-40 mt-2 max-h-64 w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                            <p x-show="excludeLoading" class="px-3 py-3 text-sm font-semibold text-slate-500">Searching...</p>
                            <p x-show="!excludeLoading && excludeResults.length === 0" class="px-3 py-3 text-sm font-semibold text-slate-500">No matching products found.</p>
                            <template x-for="option in excludeResults" :key="`exclude-result-${option.id}`">
                                <button type="button" @mousedown.prevent @click="addExcludedProduct(option)" class="block w-full rounded-lg px-3 py-2.5 text-left hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                    <strong class="block text-sm font-black text-brand-ink" x-text="option.name"></strong>
                                    <span class="mt-1 block text-xs font-medium text-slate-500" x-text="option.sku ? `SKU: ${option.sku}` : option.path"></span>
                                </button>
                            </template>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="product in excludedProducts" :key="`exclude-chip-${product.id}`">
                                <span class="inline-flex max-w-full items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700">
                                    <span class="truncate" x-text="product.name"></span>
                                    <button type="button" @click="removeExcludedProduct(product.id)" class="text-base leading-none text-red-400 hover:text-red-700" aria-label="Remove excluded product">×</button>
                                </span>
                            </template>
                        </div>
                        <p class="mt-2 text-xs font-medium leading-5 text-slate-500">Excluded products will not receive this campaign discount even if they match the selected category.</p>
                    </div>
                </x-admin.section-card>

                <x-admin.section-card title="5. Display & priority" description="Control how this campaign appears and its priority against other promotions.">
                    <input type="hidden" name="show_sale_badge" :value="showSaleBadge ? 1 : 0">
                    <input type="hidden" name="show_sale_page" :value="showSalePage ? 1 : 0">

                    <div class="np-sale-display-layout">
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <input type="checkbox" x-model="showSaleBadge" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-brand-red">
                                <span><strong class="block text-sm font-black text-brand-ink">Show sale badge on product listings and product page</strong><small class="mt-1 block text-xs font-medium text-slate-500">Displays a Sale badge on discounted products.</small></span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <input type="checkbox" x-model="showSalePage" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-brand-red">
                                <span><strong class="block text-sm font-black text-brand-ink">Also appear on the Sale page</strong><small class="mt-1 block text-xs font-medium text-slate-500">Includes these products in the dedicated Sale page.</small></span>
                            </label>
                        </div>

                        <label class="admin-label">
                            Promotional priority
                            <input type="number" name="priority" min="1" max="1000000" x-model.number="priority" class="admin-input" required>
                        </label>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-brand-blue">
                            <p class="font-black">Non-stacking rule</p>
                            <p class="mt-1 text-xs font-medium leading-5">Only one product discount applies. The highest eligible discount should be used.</p>
                        </div>
                    </div>
                </x-admin.section-card>
            </form>

            <aside class="np-sale-summary-sticky space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-xl font-black text-brand-ink">Preview and Summary</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Review how this campaign will work.</p>
                    </div>

                    <div class="np-sale-promo-banner mt-5">
                        <div class="np-sale-promo-banner__copy">
                            <strong class="block text-2xl font-black uppercase leading-none text-brand-dark" x-text="campaignName || 'SALE CAMPAIGN'"></strong>
                            <span class="mt-4 inline-flex min-h-9 items-center rounded-lg bg-brand-red px-4 text-sm font-black text-white" x-text="discountBadge"></span>
                        </div>
                        <div class="np-sale-promo-banner__stripe" aria-hidden="true"></div>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-sm font-black text-brand-ink">Campaign details</h3>
                        <dl class="mt-3 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Campaign name</dt><dd class="font-semibold text-brand-ink" x-text="campaignName || '-'"></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Status</dt><dd class="font-semibold text-brand-ink" x-text="statusLabel"></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Discount</dt><dd class="font-semibold text-brand-ink" x-text="discountSummary"></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Schedule</dt><dd class="font-semibold leading-5 text-brand-ink"><span x-text="scheduleSummary"></span><small x-show="repeatWeekdays && weekdays.length" x-cloak class="mt-1 block font-medium text-slate-500" x-text="weekdaySummary"></small></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Time zone</dt><dd class="font-semibold text-brand-ink" x-text="timezone || '-' "></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Applies to</dt><dd class="font-semibold text-brand-ink"><span x-text="appliesSummary"></span><template x-for="target in selectedTargets" :key="`summary-target-${target.id}`"><small class="mt-1 block font-medium leading-5 text-slate-500" x-text="target.path || target.name"></small></template></dd></div>
                            <div class="np-sale-summary-row py-3"><dt class="text-slate-500">Excludes</dt><dd class="font-semibold text-brand-ink"><span x-show="excludedProducts.length === 0">None</span><template x-for="product in excludedProducts" :key="`summary-excluded-${product.id}`"><small class="block font-medium leading-5 text-slate-500" x-text="product.name"></small></template></dd></div>
                        </dl>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-black text-brand-ink">Product preview</h3>
                            <span x-show="previewProducts.length > 1" x-cloak class="text-xs font-bold text-slate-500"><span x-text="previewIndex + 1"></span>/<span x-text="previewProducts.length"></span></span>
                        </div>

                        <div x-show="previewProducts.length === 0" class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-7 text-center">
                            <p class="text-sm font-black text-brand-ink">No product selected</p>
                            <p class="mt-1 text-xs font-medium leading-5 text-slate-500">Choose <strong>Specific products</strong> and select a product to see a real preview here. No demo product is used.</p>
                        </div>

                        <div x-show="previewProducts.length > 0" x-cloak class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="np-sale-product-card">
                                <div class="grid place-items-center bg-slate-50 p-3">
                                    <template x-if="previewProduct && previewProduct.imageUrl">
                                        <img :src="previewProduct.imageUrl" :alt="previewProduct.name" class="h-32 w-full object-contain">
                                    </template>
                                    <template x-if="previewProduct && !previewProduct.imageUrl">
                                        <div class="text-center text-xs font-bold text-slate-400">No product image uploaded</div>
                                    </template>
                                </div>
                                <div class="flex min-w-0 flex-col justify-center p-4">
                                    <strong class="text-sm font-black leading-5 text-brand-ink" x-text="previewProduct ? previewProduct.name : ''"></strong>
                                    <span class="mt-1 text-xs font-medium text-slate-500" x-show="previewProduct && previewProduct.sku" x-text="previewProduct ? `SKU: ${previewProduct.sku}` : ''"></span>
                                    <div class="mt-3 flex flex-wrap items-baseline gap-2">
                                        <del class="text-sm font-semibold text-slate-500" x-text="previewProduct ? money(previewPrice, previewProduct) : ''"></del>
                                        <b class="text-lg font-black text-brand-red" x-text="previewProduct ? money(previewSalePrice, previewProduct) : ''"></b>
                                    </div>
                                    <span class="mt-3 inline-flex w-fit rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-black text-brand-red" x-text="discountBadge"></span>
                                </div>
                            </div>
                            <div x-show="previewProducts.length > 1" x-cloak class="flex items-center justify-between border-t border-slate-100 px-3 py-2">
                                <button type="button" @click="previousPreviewProduct()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white font-black text-brand-ink hover:bg-slate-50" aria-label="Previous selected product">←</button>
                                <div class="flex max-w-[220px] gap-1 overflow-hidden">
                                    <template x-for="(product, index) in previewProducts" :key="`preview-dot-${product.id}`">
                                        <button type="button" @click="previewIndex = index" class="h-2.5 rounded-full transition" :class="previewIndex === index ? 'w-6 bg-brand-red' : 'w-2.5 bg-slate-300'" :aria-label="`Show ${product.name}`"></button>
                                    </template>
                                </div>
                                <button type="button" @click="nextPreviewProduct()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white font-black text-brand-ink hover:bg-slate-50" aria-label="Next selected product">→</button>
                            </div>
                        </div>
                    </div>

                    <div class="np-sale-banner-tools mt-6">
                        <div class="np-sale-banner-tools__heading">
                            <div>
                                <h3 class="text-sm font-black text-brand-ink">Banner media & placement</h3>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500">Upload responsive campaign artwork, then choose exactly where it should appear.</p>
                            </div>
                        </div>
                        <input type="hidden" name="remove_banner_image" :value="removeBannerImage ? 1 : 0" form="sale-campaign-form">
                        <input type="hidden" name="remove_banner_mobile_image" :value="removeBannerMobileImage ? 1 : 0" form="sale-campaign-form">
                        <input type="hidden" name="banner_placements_present" value="1" form="sale-campaign-form">

                        <div class="np-sale-banner-media-grid mt-4">
                            <section class="np-sale-banner-device-card">
                                <div class="np-sale-banner-device-card__head">
                                    <div>
                                        <p class="np-sale-banner-device-card__title">Desktop banner</p>
                                        <p class="np-sale-banner-device-card__hint">Wide artwork for desktop and larger screens.</p>
                                    </div>
                                    <span class="np-sale-banner-ratio" aria-label="recommended ratio 24:5">24:5 ratio</span>
                                </div>
                                <input
                                    x-ref="bannerDesktopInput"
                                    type="file"
                                    name="banner_image"
                                    form="sale-campaign-form"
                                    accept="image/jpeg,image/png,image/webp,image/avif"
                                    class="hidden"
                                    @change="handleBannerDesktopFile($event.target.files && $event.target.files[0] ? $event.target.files[0] : null)"
                                >
                                <div x-show="!bannerDesktopPreviewUrl" class="np-sale-banner-dropzone">
                                    <div class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-white text-xl shadow-sm">▧</div>
                                    <p class="mt-3 text-sm font-black text-brand-ink">No desktop banner uploaded.</p>
                                    <p class="mt-1 text-xs font-medium leading-5 text-slate-500">JPG, PNG, WebP or AVIF · up to 12MB</p>
                                    <button type="button" @click="$refs.bannerDesktopInput.click()" class="btn btn-white mt-4">Upload Desktop</button>
                                </div>
                                <div x-show="bannerDesktopPreviewUrl" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img :src="bannerDesktopPreviewUrl" alt="Selected campaign desktop banner preview" class="np-sale-upload-preview bg-slate-100">
                                    <div class="flex flex-col gap-3 p-4">
                                        <p class="truncate text-sm font-black text-brand-ink" x-text="bannerDesktopFileName"></p>
                                        <div class="flex gap-2">
                                            <button type="button" @click="$refs.bannerDesktopInput.click()" class="btn btn-white">Replace</button>
                                            <button type="button" @click="clearBannerDesktopFile()" class="btn btn-white text-red-700">Remove</button>
                                        </div>
                                    </div>
                                </div>
                                @error('banner_image')<p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>@enderror
                            </section>

                            <section class="np-sale-banner-device-card">
                                <div class="np-sale-banner-device-card__head">
                                    <div>
                                        <p class="np-sale-banner-device-card__title">Mobile banner</p>
                                        <p class="np-sale-banner-device-card__hint">Portrait-friendly artwork for phones and narrow screens.</p>
                                    </div>
                                    <span class="np-sale-banner-ratio" aria-label="recommended ratio 5:4">5:4 ratio</span>
                                </div>
                                <input
                                    x-ref="bannerMobileInput"
                                    type="file"
                                    name="banner_mobile_image"
                                    form="sale-campaign-form"
                                    accept="image/jpeg,image/png,image/webp,image/avif"
                                    class="hidden"
                                    @change="handleBannerMobileFile($event.target.files && $event.target.files[0] ? $event.target.files[0] : null)"
                                >
                                <div x-show="!bannerMobilePreviewUrl" class="np-sale-banner-dropzone">
                                    <div class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-white text-xl shadow-sm">▧</div>
                                    <p class="mt-3 text-sm font-black text-brand-ink">No mobile banner uploaded.</p>
                                    <p class="mt-1 text-xs font-medium leading-5 text-slate-500">JPG, PNG, WebP or AVIF · up to 12MB</p>
                                    <button type="button" @click="$refs.bannerMobileInput.click()" class="btn btn-white mt-4">Upload Mobile</button>
                                </div>
                                <div x-show="bannerMobilePreviewUrl" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img :src="bannerMobilePreviewUrl" alt="Selected campaign mobile banner preview" class="np-sale-upload-preview bg-slate-100">
                                    <div class="flex flex-col gap-3 p-4">
                                        <p class="truncate text-sm font-black text-brand-ink" x-text="bannerMobileFileName"></p>
                                        <div class="flex gap-2">
                                            <button type="button" @click="$refs.bannerMobileInput.click()" class="btn btn-white">Replace</button>
                                            <button type="button" @click="clearBannerMobileFile()" class="btn btn-white text-red-700">Remove</button>
                                        </div>
                                    </div>
                                </div>
                                @error('banner_mobile_image')<p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>@enderror
                            </section>
                        </div>

                        <div class="np-sale-banner-placement mt-5">
                            <div class="np-sale-banner-placement__head">
                                <div>
                                    <h4>Where should this banner appear?</h4>
                                    <p>Select one or more storefront placements. You can change these later.</p>
                                </div>
                                <span class="np-sale-banner-placement__count" x-text="`${bannerPlacements.length} selected`"></span>
                            </div>
                            @include('admin.promotions._banner-placement-fields', [
                                'placementState' => 'bannerPlacements',
                                'toggleMethod' => 'toggleBannerPlacement',
                                'fieldName' => 'banner_placements',
                                'formId' => 'sale-campaign-form',
                            ])
                            @error('banner_placements')<p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>@enderror
                            @error('banner_placements.*')<p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="admin-label">
                                Banner heading
                                <input type="text" name="banner_heading" form="sale-campaign-form" x-model="bannerHeading" class="admin-input" maxlength="255" :placeholder="campaignName || 'Campaign banner heading'">
                                @error('banner_heading')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            </label>

                            <label class="admin-label">
                                Image alt text
                                <input type="text" name="banner_alt_text" form="sale-campaign-form" x-model="bannerAltText" class="admin-input" maxlength="255" :placeholder="campaignName ? `${campaignName} banner` : 'Campaign banner'">
                                @error('banner_alt_text')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            </label>

                            <label class="admin-label">
                                CTA label
                                <input type="text" name="banner_cta_label" form="sale-campaign-form" x-model="bannerCtaLabel" class="admin-input" maxlength="80" placeholder="Shop Sale">
                                @error('banner_cta_label')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            </label>

                            <label class="admin-label">
                                Destination link
                                <input type="text" name="banner_destination_link" form="sale-campaign-form" x-model="bannerDestinationLink" class="admin-input" maxlength="2048" placeholder="/sale">
                                @error('banner_destination_link')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end">
            <button type="submit" form="sale-campaign-form" name="submit_action" value="draft" class="btn btn-white">Save Draft</button>
            <button type="submit" form="sale-campaign-form" name="submit_action" value="publish" class="btn btn-red">Publish Campaign</button>
        </div>
    </div>
</x-layouts.admin>
