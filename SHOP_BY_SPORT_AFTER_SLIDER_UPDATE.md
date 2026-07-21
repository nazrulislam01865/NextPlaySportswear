# Shop by Sport After Slider Update

The **Shop by Sport** homepage section now renders immediately after the top homepage image slider and before the separate Hero Banner section.

Changed files:

- `app/Support/HomepageSectionRegistry.php`
  - Changed the default `shop_by_sport` order from `25` to `15`.
- `database/migrations/2026_07_18_000003_move_shop_by_sport_after_homepage_slider.php`
  - Updates existing database rows to order `15`.
- `app/Services/Storefront/HomepageSectionService.php`
  - Bumped the homepage-section cache key so the new order is used immediately after deployment.
- `tests/Unit/HomepageSectionOrderTest.php`
  - Confirms that Shop by Sport is directly after the slider in the default section registry.

Run after upload:

```bash
php artisan migrate --force
php artisan optimize:clear
```
