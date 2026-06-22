@props(['title', 'description' => null, 'id' => null])
<section @if($id) id="{{ $id }}" @endif class="scroll-mt-28 rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
    <div class="mb-5 border-b border-slate-100 pb-4">
        <h2 class="text-xl font-black text-brand-ink">{{ $title }}</h2>
        @if($description)<p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</section>
