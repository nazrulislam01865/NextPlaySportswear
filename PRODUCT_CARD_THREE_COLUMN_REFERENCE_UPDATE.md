# All Products three-column reference card update

- The All Products storefront grid now uses three cards per desktop row, two on tablets, and one on phones.
- Removed the desktop four-column breakpoint from `resources/views/storefront/products/index.blade.php`.
- Product cards now follow the supplied reference card proportions, spacing, typography, media treatment, favorite control, rating row, visitor row, price row, CTA, and details link.
- The card uses the existing NextPlay navy, red, cyan, and gold colors.
- Inter is explicitly used throughout the card to match the reference typography.
- Only the first two genuine customization options are displayed, matching the reference layout without an extra `+N more` row.
- Existing genuine rating, review, bulk-pricing, favorite, and live visitor behavior remains intact.
