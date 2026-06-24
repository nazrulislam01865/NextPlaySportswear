@props(['group'])
@php($id = $group['id'])
<section class="border-t border-slate-100 pt-6 first:border-t-0 first:pt-0">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h4 class="text-lg font-black text-brand-ink">{{ $group['label'] }} @if($group['required'])<span class="text-brand-red">*</span>@endif</h4>
            @if($group['description'])<p class="mt-1 text-sm leading-6 text-slate-500">{{ $group['description'] }}</p>@endif
        </div>
    </div>

    @if($group['type'] === 'image')
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($group['values'] as $value)
                @php($images = collect($value['images'] ?? [])->map(fn ($image) => is_array($image) ? ($image['url'] ?? null) : $image)->filter()->values())
                <button type="button" @click="choose(@js($group), @js($value['id']))" :class="selections[@js($id)] === @js($value['id']) ? 'border-brand-red ring-2 ring-red-100' : 'border-slate-200'" class="min-w-0 overflow-hidden rounded-2xl border-2 bg-white p-2 text-left transition">
                    @if($images->isNotEmpty())
                        <img src="{{ $images->first() }}" alt="{{ $value['label'] }} fabric or option preview" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-xl object-cover">
                        @if($images->count() > 1)
                            <div class="mt-2 grid grid-cols-4 gap-1.5">
                                @foreach($images->take(4) as $image)<img src="{{ $image }}" alt="" loading="lazy" decoding="async" class="aspect-square w-full rounded-lg border border-slate-200 object-cover">@endforeach
                            </div>
                        @endif
                    @elseif($value['image'])
                        <img src="{{ $value['image'] }}" alt="{{ $value['label'] }} fabric or option preview" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-xl object-cover">
                    @else
                        <div class="grid aspect-[4/3] place-items-center rounded-xl bg-slate-100 px-4 text-center text-sm font-black text-slate-400">No option image</div>
                    @endif
                    <div class="p-2">
                        <div class="flex items-start justify-between gap-3"><strong class="min-w-0 break-words text-sm">{{ $value['label'] }}</strong><span class="shrink-0 text-xs font-black text-brand-red" x-text="chargeLabel(@js($value))"></span></div>
                        @if($value['description'])<p class="mt-1 text-xs leading-5 text-slate-500">{{ $value['description'] }}</p>@endif
                    </div>
                </button>
            @endforeach
        </div>
    @elseif($group['type'] === 'swatch')
        <div class="mt-4 flex flex-wrap gap-4">
            @foreach($group['values'] as $value)
                <button type="button" @click="choose(@js($group), @js($value['id']))" class="group grid justify-items-center gap-2" title="{{ $value['label'] }}">
                    <span :class="selections[@js($id)] === @js($value['id']) ? 'ring-4 ring-brand-red' : 'ring-1 ring-slate-300'" class="grid h-12 w-12 place-items-center rounded-full border-4 border-white shadow-sm" style="background-color: {{ $value['color'] ?: '#E2E8F0' }}"><span x-show="selections[@js($id)] === @js($value['id'])" class="font-black drop-shadow" style="color: {{ $value['contrast'] ?? '#0F172A' }}">✓</span></span>
                    <span class="max-w-[100px] text-center text-[10px] font-black text-slate-600">{{ $value['label'] }}</span>
                    <span class="text-[9px] font-bold text-brand-red" x-text="chargeLabel(@js($value))"></span>
                </button>
            @endforeach
        </div>
    @elseif(in_array($group['type'], ['buttons','select']))
        @if($group['type'] === 'buttons')
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($group['values'] as $value)
                    <button type="button" @click="choose(@js($group), @js($value['id']))" :class="selections[@js($id)] === @js($value['id']) ? 'border-brand-red bg-red-50 text-brand-red' : 'border-slate-300 bg-white text-slate-700'" class="rounded-xl border px-4 py-3 text-left text-sm font-black"><span>{{ $value['label'] }}</span><small class="ml-1" x-text="chargeLabel(@js($value))"></small></button>
                @endforeach
            </div>
        @else
            <select class="mt-4 h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold" x-model="selections[@js($id)]" @change="sync()">
                <option value="">Select {{ $group['label'] }}</option>
                @foreach($group['values'] as $value)<option value="{{ $value['id'] }}">{{ $value['label'] }}{{ $value['price_delta'] != 0 ? ' — '.($value['price_delta'] > 0 ? '+' : '−').'$'.number_format(abs($value['price_delta']),2).(($value['charge_type'] ?? 'per_unit') === 'fixed_order' ? ' / order' : ' / piece') : ' — Included' }}</option>@endforeach
            </select>
        @endif
    @elseif($group['type'] === 'checkbox')
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach($group['values'] as $value)
                <label :class="(multiSelections[@js($id)] || []).includes(@js($value['id'])) ? 'border-brand-blue bg-blue-50' : 'border-slate-200 bg-white'" class="flex cursor-pointer gap-3 rounded-2xl border p-4">
                    <input type="checkbox" :checked="(multiSelections[@js($id)] || []).includes(@js($value['id']))" @change="toggle(@js($group), @js($value['id']))" class="mt-1">
                    @if($value['image'])<img src="{{ $value['image'] }}" alt="" class="h-12 w-12 shrink-0 rounded-xl border border-slate-200 object-cover" loading="lazy" decoding="async">@endif
                    <span class="min-w-0"><strong class="block break-words text-sm">{{ $value['label'] }} <small class="text-brand-red" x-text="chargeLabel(@js($value))"></small></strong>@if($value['description'])<small class="mt-1 block text-xs leading-5 text-slate-500">{{ $value['description'] }}</small>@endif</span>
                </label>
            @endforeach
        </div>
    @elseif($group['type'] === 'textarea')
        <textarea x-model="inputs[@js($id)]" @input="sync()" class="mt-4 min-h-[120px] w-full rounded-xl border border-slate-300 p-4 text-sm" placeholder="{{ $group['placeholder'] }}"></textarea>
    @elseif($group['type'] === 'file')
        <label class="mt-4 block rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center"><strong class="block text-sm">Upload {{ $group['label'] }}</strong><small class="mt-1 block text-xs text-slate-500">{{ $group['accepted_file_types'] ?: 'PDF, SVG, PNG, JPG' }} · max {{ $group['maximum_file_size_mb'] ?: 15 }} MB</small><input type="file" class="mt-4 text-sm" name="artwork_file" @change="inputs[@js($id)] = $event.target.files[0]?.name || ''; sync()"></label>
    @else
        <input class="mt-4 h-12 w-full rounded-xl border border-slate-300 px-4 text-sm" type="{{ $group['type'] === 'number' ? 'number' : ($group['type'] === 'date' ? 'date' : 'text') }}" x-model="inputs[@js($id)]" @input="sync()" placeholder="{{ $group['placeholder'] }}">
    @endif
</section>
