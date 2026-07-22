# Best-Selling Team Gear Redesign

Updated the homepage Best-Selling Team Gear section to use a dark navy sports-inspired layout with a featured primary card and four supporting category cards.

## Updated files

- `resources/views/components/storefront/home/best-selling-gear.blade.php`
- `resources/css/storefront.css`
- `public/build/assets/storefront-5C_uabZA.css`
- `app/Services/Storefront/HomePageService.php`
- `app/Support/HomepageSectionRegistry.php`
- `app/Http/Requests/Admin/HomepageSectionRequest.php`
- `resources/views/admin/homepage-sections/edit.blade.php`

## Admin behavior

The Best-Selling Gear homepage section now supports admin-managed card order and optional overrides for:

- Category / subcategory / sub-subcategory
- Card title
- Card description
- Image URL
- Image alt text
- Link URL
- CTA label

If no custom cards are configured, the storefront falls back to the automatic catalog selection and prioritizes Performance Apparel, Accessories, Bags, Drinkware, and Headwear when those catalog categories are available.
