<x-admin.homepage.section-panel title="Make It Yours" description="Product cards remain catalog-driven; customize the section heading and Explore All link.">
    <div class="grid gap-4 md:grid-cols-2">
        <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />
        <div></div>
        <x-admin.homepage.text-field name="primary_label" label="Explore link label" :value="$viewSection['primary_label']" />
        <x-admin.homepage.link-field name="primary_url" label="Explore link URL" :value="$viewSection['primary_url']" />
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
