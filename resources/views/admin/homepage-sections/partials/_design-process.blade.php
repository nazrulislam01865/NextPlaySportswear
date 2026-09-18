<x-admin.homepage.section-panel title="Design Process" description="Edit the five design-process steps, including the image shown with each step.">
    <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />
    <div class="mt-5 space-y-4">
        @foreach($viewSection['items'] as $index => $item)
            <x-admin.homepage.item-card :title="str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT).' · '.($item['title'] ?? 'Step')">
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.homepage.text-field name="items[{{ $index }}][title]" old-name="items.{{ $index }}.title" label="Step title" :value="$item['title'] ?? ''" />
                    <x-admin.homepage.text-field name="items[{{ $index }}][description]" old-name="items.{{ $index }}.description" label="Description" :value="$item['description'] ?? ''" textarea />
                </div>
                <div class="mt-4"><x-admin.homepage.media-field label="Step image" prefix="items[{{ $index }}]" :image="$item['image'] ?? null" :image-url="$item['image_url'] ?? null" :image-alt="$item['image_alt'] ?? null" /></div>
            </x-admin.homepage.item-card>
        @endforeach
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
