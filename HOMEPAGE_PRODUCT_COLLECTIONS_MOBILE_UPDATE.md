# Homepage Product Collections and Mobile List Update

## Updated behavior

- Featured Products keeps the existing desktop card grid.
- On screens up to 640px, Featured Products uses the compact image-and-title list shown in the supplied reference.
- Added independent **Latest Products** and **Best Selling Products** homepage sections.
- Both new sections are available in **Admin → Homepage Sections**, including visibility, heading text, and sort order controls.
- Latest Products is selected automatically using publication and creation dates.
- Best Selling Products is ranked from the net quantity in paid orders, excluding cancelled and returned quantities. Until sales exist, the newest active products fill the section.
- Desktop and large-screen product cards were not redesigned.

## Main files

- `app/Services/Storefront/ProductCatalogService.php`
- `app/Services/Storefront/HomePageService.php`
- `app/Support/HomepageSectionRegistry.php`
- `resources/views/storefront/home.blade.php`
- `resources/views/components/storefront/home/product-collection.blade.php`
- `resources/views/components/storefront/home/featured-products.blade.php`
- `resources/views/components/storefront/home/latest-products.blade.php`
- `resources/views/components/storefront/home/best-selling-products.blade.php`
- `resources/css/storefront.css`
