@props(['title', 'description' => null, 'id' => null])
<section @if($id) id="{{ $id }}" @endif class="admin-section-card scroll-mt-24 rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
    <div class="admin-section-card__header mb-5 border-b border-slate-100 pb-4">
        <h2 class="admin-section-card__title text-xl font-black text-brand-ink">{{ $title }}</h2>
        @if($description)<p class="admin-section-card__description mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</section>
