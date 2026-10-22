# NextPlay product detail and category hierarchy integration

## Scope

The FlowTracker product detail view follows the supplied NextPlay Sportswear design: gallery, basic information, top navigation, collapsible configuration/size/pricing/image/classification/document panels, and activity. Reuses existing permissions, product edit, documents and actions. No order, inquiry or artwork workflow changes.

The NPS server exposes read-only, token-authenticated, cursor-paginated category records (`/api/integrations/flowtrack/catalog/categories`, limit 100) and a single-category endpoint. The product summary contains primary, additional, and legacy category references. Product details provide public configuration only (no internal cost price or customer-private fields).

FlowTracker mirrors the hierarchy under its reserved `NEXTPLAY-CATALOG` branch using codes `NP-CAT-{NPS_ID}` and records the original IDs in metadata. A dedicated `NextPlay Categories` master category groups these external category records. Non-NPS categories are not deleted or overwritten. NPS category edits send one queued change event, rather than a full catalog import on every page visit. Linked product parent assignments are refreshed when an NPS product update is imported.

## Local setup

1. Install both updated projects, keeping their existing `.env`, databases, uploaded files and APP_KEY values.
2. Ensure NPS / FlowTracker catalog integration settings and matching authenticated tokens are configured.
3. Run FlowTracker `npm ci && npm run build` to compile the updated scoped stylesheet. Run `php artisan optimize:clear` in both projects, then restart both queue workers.
4. Once NPS is reachable from FlowTracker, run **once**: `php artisan nextplay:sync-catalog` in FlowTracker. This imports the category tree first, then refreshes product assignments through cursor pages. To refresh only the category tree later, use `php artisan nextplay:sync-catalog --categories-only` (products already linked to existing category IDs continue to reference them).
5. Open a linked NPS product detail from FlowTracker. The gallery/metadata render immediately; detailed NPS data is requested when the Configuration section approaches the viewport and cached briefly. The Refresh details button explicitly refreshes it. Inactive/missing data shows a documented fallback instead of speculative values.
6. To run the source guard: `php scripts/quality/nextplay-product-details.php`.

## Verification checklist

- [ ] Confirm that opening the same linked product in NPS and FlowTracker shows the same name, images, sizes, custom options and price tiers.
- [ ] Verify mobile/tablet/desktop layout against the supplied screenshot, plus text overflow and gallery switching.
- [ ] Expand/collapse all panels, use the tab jump navigation, copy code, view/download images and documents, and open Edit product if authorized.
- [ ] Move or rename an NPS category: verify the queued event updates its mirrored parent/name without altering local categories.
- [ ] Test primary and secondary NPS product assignments, category deletion/inactivation, and a leaf category under a nested parent.
- [ ] Confirm no full-catalog request is made during ordinary page loads; category imports happen through CLI/queued events only.
- [ ] Verify NextPlay approvals and operational workflow functionality still behaves as before.

## Limits

- No DB migration is needed. No source project databases were provided, so running integration/visual regression tests is required locally before production.
- The `Add images` control reuses FlowTracker's existing product editor; images mastered in NPS should be updated in NPS and then refreshed here.
- Full NPS category ancestry is preserved by source parent IDs and FlowTracker parent links. The existing FlowTracker taxonomy editor is a legacy three-level grouping, so deeply nested NPS categories may not display as nested editor rows at every depth. The product detail page shows the normal ancestry context for the current item.
