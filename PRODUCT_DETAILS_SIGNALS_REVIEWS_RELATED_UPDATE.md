# Product details signals, reviews, and related products update

Implemented on the storefront product details page:

- Product rating and review count directly below the product title.
- Functional local favorite/save control.
- Native share button with clipboard fallback.
- Genuine live shopper activity in the title area using the existing product visitor tracker.
- Customer review summary near the end of the product page.
- Written review cards only when genuine review entries are present in the product JSON-LD `review`/`reviews` data.
- Verified Buyer badge only when the review data explicitly marks the review as verified.
- Compact four-column related-products section matching the supplied prototype structure and the NextPlay color theme.
- Aggregate rating and genuine written reviews added to product structured data.

No placeholder visitor counts, favorite counts, order counts, review quotes, or reviewer identities are generated.

## Review JSON example

Product-specific written reviews can be supplied through the existing product schema JSON field:

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "review": [
    {
      "@type": "Review",
      "name": "Great quality and fit",
      "reviewBody": "The jerseys looked great and arrived exactly as approved.",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": 5
      },
      "author": {
        "@type": "Person",
        "name": "Marcus T.",
        "jobTitle": "Youth Basketball Coach"
      },
      "verified": true,
      "datePublished": "2026-07-20"
    }
  ]
}
```

## Deployment

```bash
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
```

No new database migration is required for this update. The previously added `product_view_sessions` migration is still required for real-time visitor activity.
