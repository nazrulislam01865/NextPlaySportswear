@php($aspect = \App\Support\HomepageImageAspectRatios::forSectionItem('audience'))
<x-admin.homepage.section-panel title="Audience Tiles" description="Control the MEN, WOMEN and KIDS tiles. Each image can be uploaded or replaced here.">
    <div class="space-y-4">
        @foreach($viewSection['items'] as $index => $item)
            <x-admin.homepage.item-card :title="$item['title'] ?? strtoupper($item['id'] ?? 'Tile')">
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.homepage.text-field name="items[{{ $index }}][title]" old-name="items.{{ $index }}.title" label="Tile title" :value="$item['title'] ?? ''" />
                    <x-admin.homepage.link-field name="items[{{ $index }}][url]" old-name="items.{{ $index }}.url" label="Destination URL" :value="$item['url'] ?? ''" />
                </div>
                <div class="mt-4"><x-admin.homepage.media-field label="Tile image" prefix="items[{{ $index }}]" :image="$item['image'] ?? null" :image-url="$item['image_url'] ?? null" :image-alt="$item['image_alt'] ?? null" :aspect-ratio="$aspect['ratio']" :aspect-tolerance="(int) round($aspect['tolerance'] * 100)" /></div>
            </x-admin.homepage.item-card>
        @endforeach
        <x-admin.homepage.visibility-field :active="$viewSection['is_active']" />
    </div>
</x-admin.homepage.section-panel>
