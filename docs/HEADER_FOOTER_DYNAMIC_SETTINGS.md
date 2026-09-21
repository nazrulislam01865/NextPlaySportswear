# Dynamic Header and Footer Settings

This update extends the new Vue storefront settings only.

## Header
- Multiple announcements (up to 12), with show/hide, optional URL, dismissible flag and ordering.
- Enabled announcements rotate automatically in the Vue utility bar.
- Multiple utility links (up to 12), with show/hide, label, URL, icon and ordering.
- Header primary navigation continues to use the existing Navigation Menus system.

## Footer
- Dynamic footer menu columns (up to 6).
- Each column supports multiple links (up to 12), optional icons and ordering.
- Dynamic social links (up to 12) with icon/network selection and ordering.
- Dynamic legal links (up to 10), optional icons and ordering.
- Existing contact, club CTA and payment trust controls remain.

## Data contract
The existing `storefront_settings` JSON storage is reused, so no new database migration is required for this update. The service normalizes the first version of header/footer JSON automatically, preserving previously saved content.

The Vue storefront consumes the updated arrays through `/api/v1/storefront/bootstrap`.
