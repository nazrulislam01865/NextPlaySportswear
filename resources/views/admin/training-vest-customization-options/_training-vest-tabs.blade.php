<x-admin.customization-type-tabs
    :heading="$type->groupNumber().' '.$type->groupLabel().' Submenus'"
    :aria-label="$type->groupLabel().' type navigation'"
    :links="$typeLinks"
    :active-type="$type"
    route-name="admin.training-vest-customization-options.type"
/>
