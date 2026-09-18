<x-admin.homepage.section-panel title="New Arrivals" description="The products are catalog-driven; only the section heading and visibility are managed here.">
    <div class="grid gap-4 md:grid-cols-2"><x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" /><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
