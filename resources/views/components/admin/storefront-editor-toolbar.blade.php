@props([
    'description',
    'previewUrl',
    'previewLabel',
    'saveLabel',
    'canManage' => true,
])

<div class="np-storefront-editor__toolbar mb-1 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <p class="text-sm leading-6 text-slate-500">{{ $description }}</p>
    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-white">{{ $previewLabel }}</a>
        <button type="submit" class="btn btn-red" @disabled(! $canManage)>{{ $saveLabel }}</button>
    </div>
</div>
