@props(['tips' => []])

<div class="mt-6 grid gap-3 sm:grid-cols-3">
    @foreach ($tips as $tip)
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <strong class="text-sm font-black text-brand-ink">{{ $tip['title'] }}</strong>
            <p class="mt-1 text-xs font-bold leading-5 text-slate-600">{{ $tip['body'] }}</p>
        </div>
    @endforeach
</div>
