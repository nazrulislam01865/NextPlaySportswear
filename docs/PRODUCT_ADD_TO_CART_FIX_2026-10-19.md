# Product Add to Cart fix — October 19, 2026

## Why the cart button could not be used

The product detail builder set `canAddToCart()` to `activeCustomizerStep === 6`. The actual review step is **5** when the product has no player/roster step, and **6** otherwise. In addition, all accordion panels can be expanded without updating `activeCustomizerStep`, so the button could remain disabled even after all required configuration was filled out.

## Focused changes

- The Add to Cart buttons in the review section and persistent order summary now become available whenever the actual configuration is valid, regardless of the last accordion clicked.
- When the currently active accordion is the **actual final review step**, the button can still be clicked even if required data is missing. The existing `validate()` function then shows an actionable validation message and opens the relevant step. This preserves the project's previous feedback behavior.
- The order summary now uses the project's actual dynamic artwork/production/roster step numbers. Products with no roster step no longer show an unusable roster shortcut.
- When Add to Cart is unavailable before the review step, the sidebar shows the concrete missing requirement instead of only the generic “Complete all required steps” message.
- When quantity-specific production methods are enabled and no matching rule exists, client validation now matches the existing cart backend's error.
- No pricing, discount, server cart service, routes, database, CSS, admin or artwork approval code was changed.

## Apply the changed-files patch

1. Back up your current project or commit local changes.
2. Unzip the **Changed Files** archive into your NextPlaySportswear project root, preserving directories.
3. Rebuild the storefront assets to include the updated `resources/js/storefront.js`:

   ```bash
   npm run build
   php artisan view:clear
   php artisan optimize:clear
   ```

If you have not yet applied the separate prior CSS circular-dependency correction, that existing Vite issue must be addressed before `npm run build` can succeed. This patch does **not** modify CSS.

## Recommended manual checks

1. Product with no roster: fill MOQ and other required options; Add to Cart should work without clicking a particular accordion header. Sidebar Artwork and Production & Shipping shortcuts should open steps 3 and 4.
2. Product with roster: fill required roster details; both buttons should work on a valid configuration. Sidebar links should open steps 3, 4, and 5.
3. On the last review step with required data missing, click Add to Cart; an existing validation notification should explain what's missing and open the relevant section.
4. With an incomplete configuration before the final review step, the persistent sidebar should explain the first missing requirement.
5. If production methods are enabled and none covers the selected quantity, an actionable production-rule message should appear before submission.
6. Verify successful cart navigation, totals, discounted prices, and editing existing cart items remain as before.

## Checks performed in source-only environment

Passed: six dynamic `canAddToCart()` behavior scenarios (five-/six-step, valid/invalid), JS syntax check, and these standalone regression checks:

- `tests/Regression/ProductDetailAddToCartReadyTest.php`
- `tests/Regression/ProductReviewAndSidebarFinalTest.php`
- `tests/Regression/ProductDetailResponsiveAndMoqTest.php`
- `tests/Regression/ProductDetailStepScrollAndMaterialTableTest.php`
- `tests/Regression/ProductDetailAccordionHoverRosterPromptTest.php`

An existing legacy full-prototype contract test fails in the **original uploaded project**, because it expects outdated literal six-step template markup. It is unchanged by this patch. Full Laravel/Vite tests were not executable in the uploaded source-only project because its `vendor/` and `node_modules/` directories are absent. Please test with your local dependencies and an actual product record.
