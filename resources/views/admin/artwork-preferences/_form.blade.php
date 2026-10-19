@php
    $isEdit = $preference->exists;
    $canManage = auth('admin')->user()?->canAdmin('customization.manage') ?? false;
    $isActive = (bool) old('is_active', $preference->is_active ?? true);
    $screenHex = old('hex_color', $preference->hex_color ?: '#D22630');
@endphp

<div class="mb-5 flex items-center justify-between gap-3">
    <a class="text-sm font-semibold text-brand-red hover:underline" href="{{ route('admin.artwork-preferences.index', $type) }}">← Back to {{ $heading }}</a>
</div>

@if($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-bold">Please correct the fields below.</p>
        <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.artwork-preferences.update', [$type, $preference]) : route('admin.artwork-preferences.store', $type) }}" class="space-y-5">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-1 text-base font-bold text-brand-ink">{{ $type === 'pantone' ? 'Pantone Color Details' : 'Design Style Details' }}</h2>
        <p class="mb-5 text-sm text-slate-500">{{ $type === 'pantone' ? 'Give this color a recognizable name, its Pantone reference and a visual screen color.' : 'Create a clear design direction customers can select.' }}</p>
        <div class="grid gap-5 md:grid-cols-2">
            <label class="admin-label">Name <span class="text-brand-red">*</span>
                <input class="admin-input" name="name" value="{{ old('name', $preference->name) }}" placeholder="{{ $type === 'pantone' ? 'Red' : 'Modern' }}" maxlength="160" required @disabled(!$canManage)>
                @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <label class="admin-label">{{ $type === 'pantone' ? 'Pantone Code' : 'Style Code' }} <span class="text-brand-red">*</span>
                <input class="admin-input" name="code" value="{{ old('code', $preference->code) }}" placeholder="{{ $type === 'pantone' ? '186 C' : 'modern' }}" maxlength="80" required @disabled(!$canManage)>
                <span class="mt-1 block text-xs text-slate-500">{{ $type === 'pantone' ? 'Use the Pantone reference from your official swatch guide.' : 'Unique internal code (letters, numbers, spaces, dots or hyphens).' }}</span>
                @error('code')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
        </div>
        @if($type === 'pantone')
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50/50 p-4" x-data="{ hex: @js($screenHex) }">
                <h3 class="text-sm font-bold text-brand-ink">Choose screen preview color</h3>
                <p class="mb-3 mt-1 text-xs text-slate-500">Pick a visible color or enter its HEX value. The preview updates immediately.</p>
                <div class="flex flex-wrap items-end gap-4">
                    <label class="admin-label">Color picker
                        <input type="color" aria-label="Choose a screen preview color" class="mt-1 h-11 w-20 cursor-pointer rounded-lg border border-slate-300 bg-white p-1" :value="/^#[a-fA-F0-9]{6}$/.test(hex) ? hex : '#D22630'" @input="hex = $event.target.value.toUpperCase()" @disabled(!$canManage)>
                    </label>
                    <label class="admin-label w-full max-w-[200px]">HEX color <span class="text-brand-red">*</span>
                        <input type="text" name="hex_color" class="admin-input font-mono uppercase" x-model="hex" maxlength="7" placeholder="#D22630" required pattern="#[a-fA-F0-9]{6}" @disabled(!$canManage)>
                        @error('hex_color')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="flex items-center gap-3 pb-1">
                        <span class="h-12 w-12 rounded-xl border border-slate-300 shadow-sm" :style="{ backgroundColor: /^#[a-fA-F0-9]{6}$/.test(hex) ? hex : 'transparent' }" role="img" aria-label="Selected color preview"></span>
                        <span class="text-sm font-semibold text-slate-700">Live preview</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">HEX is for website previews, not a certified conversion from the Pantone reference.</p>
            </div>
        @endif
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <label class="admin-label">Display Order
                <input type="number" class="admin-input" name="sort_order" value="{{ old('sort_order', $preference->sort_order ?? 0) }}" min="0" max="1000000" @disabled(!$canManage)>
            </label>
            <div class="flex items-end">
                <label class="flex min-h-11 w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="is_active" value="0" @disabled(!$canManage)>
                    <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-4 w-4 accent-orange-600" @disabled(!$canManage)>
                    Active — available for product setup
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-3">
        <a class="btn btn-white" href="{{ route('admin.artwork-preferences.index', $type) }}">{{ $canManage ? 'Cancel' : 'Back' }}</a>
        @if($canManage)<button class="btn btn-red" type="submit">{{ $isEdit ? 'Save Changes' : 'Create '.($type === 'pantone' ? 'Pantone Color' : 'Design Style') }}</button>@endif
    </div>
</form>
