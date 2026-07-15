# Product Customization Item Layout Update

## Scope

This update changes only the selected Jersey Customization item layout inside the admin Add/Edit Product form. No database, validation, pricing, master-data, cart, storefront, or order logic was changed.

## Updated layout

Each selected master-data item now uses one compact card:

1. Thumbnail/color preview
2. Item name
3. Description and color value when available
4. Additional charge
5. Charge basis
6. Default choice
7. Remove item action

The three editable controls appear below the item identity area as aligned label/control rows. On small screens, each label stacks above its control.

## Reusable view

The selected-item markup was extracted into:

`resources/views/admin/products/partials/_selected-jersey-option-item.blade.php`

The main product form includes this partial inside the existing Alpine `x-for` loop.

## Files changed

- `resources/views/admin/products/_form.blade.php`
- `resources/views/admin/products/partials/_selected-jersey-option-item.blade.php`
- `public/build/manifest.json`
- `public/build/assets/app-ZrEATAw3.css`
- `public/build/assets/app-eh9poaJ6.js`

## Deployment

No migration is required.

```bash
php artisan optimize:clear
php artisan view:cache
```

The rebuilt Vite assets are included.
