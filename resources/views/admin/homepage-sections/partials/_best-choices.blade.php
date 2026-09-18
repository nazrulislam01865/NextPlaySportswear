<x-admin.homepage.section-panel title="Best Choices For You" description="Products stay catalog-driven. Customize the heading and which product-feed tabs customers can use.">
    <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />
    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        @foreach(['featured','popular','trending'] as $tab)
            @php($row = $viewSection['settings']['tabs'][$tab] ?? ['label' => strtoupper($tab), 'enabled' => true])
            <div class="np-home-admin__item-card rounded-2xl p-4">
                <x-admin.homepage.text-field name="settings[tabs][{{ $tab }}][label]" old-name="settings.tabs.{{ $tab }}.label" :label="ucfirst($tab).' tab label'" :value="$row['label']" />
                <label class="mt-3 inline-flex items-center gap-2"><input type="hidden" name="settings[tabs][{{ $tab }}][enabled]" value="0"><input type="checkbox" name="settings[tabs][{{ $tab }}][enabled]" value="1" @checked(old('settings.tabs.'.$tab.'.enabled', $row['enabled']))> Enabled</label>
            </div>
        @endforeach
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
