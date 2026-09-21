@php
$partial = match ($section->key) {
    'hero' => 'admin.homepage-sections.partials._hero',
    'audience' => 'admin.homepage-sections.partials._audience',
    'shop_by_sport' => 'admin.homepage-sections.partials._shop-by-sport',
    'new_arrivals' => 'admin.homepage-sections.partials._new-arrivals',
    'shop_by_category' => 'admin.homepage-sections.partials._shop-by-category',
    'best_choices' => 'admin.homepage-sections.partials._best-choices',
    'season_sale' => 'admin.homepage-sections.partials._season-sale',
    'make_it_yours' => 'admin.homepage-sections.partials._make-it-yours',
    'design_process' => 'admin.homepage-sections.partials._design-process',
    default => abort(404),
};
@endphp
<x-layouts.admin :title="$viewSection['name']" eyebrow="Homepage Controls" :subtitle="'Edit '.$viewSection['name'].' without changing storefront code.'" :storefront-url="route('home')">
<script>
window.npHomepageItems = (initial = []) => ({
    items: Array.isArray(initial) ? initial : [],
    add(kind) {
        const id = `${kind}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
        this.items.push({ id, category_id: '', title: '', url: '', image_url: '', image_alt: '', image_upload_token: '' });
    },
    remove(index) { this.items.splice(index, 1); }
});
</script>
<div class="np-home-admin">
    @if($errors->any())
        <div class="np-home-admin__errors mb-5 rounded-2xl p-4"><strong>Please correct the highlighted information.</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('admin.homepage.sections.update', $section->key) }}" enctype="multipart/form-data" class="space-y-5" data-homepage-upload-form data-homepage-upload-base-url="{{ url('/admin/homepage-media-uploads') }}">
        @csrf
        @method('PATCH')
        @include($partial)
        <x-admin.homepage.save-bar />
    </form>
</div>
</x-layouts.admin>
