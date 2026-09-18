@props(['active' => true])
<label class="np-home-admin__visibility flex items-center justify-between gap-4">
    <span><strong>Show this section</strong><small class="np-home-admin__muted block">Turn this off to hide the section from the storefront.</small></span>
    <span class="inline-flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $active)) class="np-home-admin__check">
        <span>Visible</span>
    </span>
</label>
