<x-admin.homepage.section-panel title="Season Sale" description="Control every editable text and the responsive banner image in the sale section.">
    <div class="grid gap-4 md:grid-cols-2">
        <x-admin.homepage.text-field name="title" label="Headline" :value="$viewSection['title']" />
        <x-admin.homepage.text-field name="description" label="Subtitle" :value="$viewSection['description']" />
        <x-admin.homepage.text-field name="primary_label" label="Button label" :value="$viewSection['primary_label']" />
        <x-admin.homepage.link-field name="primary_url" label="Button URL" :value="$viewSection['primary_url']" />
    </div>
    <div class="mt-5">
        <x-admin.homepage.media-field label="Banner image" :image="$viewSection['image']" :image-url="$viewSection['image_url']" :image-alt="$viewSection['image_alt']" />
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
