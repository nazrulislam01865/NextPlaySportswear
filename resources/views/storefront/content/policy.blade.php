<x-layouts.storefront :seo="[
    'title' => $title . ' | ' . config('storefront.name'),
    'description' => $description,
]">
    <x-storefront.content.policy-shell :eyebrow="$eyebrow" :title="$title" :description="$description" :updated="$updated" :sections="$sections" />

    <x-storefront.content.cta
        title="Have a Question About This Policy?"
        description="Contact support with the policy section and the specific question or request you would like us to review."
        primary-label="Contact Us"
        :primary-href="route('contact')"
        secondary-label="Help Center"
        :secondary-href="route('faq')"
    />
</x-layouts.storefront>
