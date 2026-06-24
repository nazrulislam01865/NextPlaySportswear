# Product Production Options by Quantity Range

## Admin workflow

- Every row in the Visible Storefront Pricing Table creates one read-only production quantity range.
- Each range has an **Add Production Option** button.
- Clicking the button opens a compact modal for:
  - Production option name
  - Separate charge per piece
  - Minimum production days
  - Maximum production days
  - Optional customer-facing description
- A range can contain zero, one, two, or three production options.
- The first option in a range is the default customer selection.
- Existing options can be edited or removed from their range card.
- No empty production row is automatically created.

## Storefront behavior

- The customer's total size quantity determines the active quantity range.
- Only production options assigned to that range are shown.
- The customer can choose one available production option.
- If the range has no production options, no production selector or production charge is shown.
- The selected option's per-piece production charge is included in the live estimate and recalculated on the server.
- When quantity moves to another range, the first option of the new range becomes the default unless the current selection is valid there.

## Security and persistence

- Quantity ranges submitted from the production section are ignored as pricing authority; the server reapplies ranges from the Visible Storefront Pricing Table.
- The backend limits each range to three production options.
- The selected production option is accepted only when it belongs to the active quantity range.
- Invalid or manipulated option IDs fall back to the first valid option in the range, or no option when the range is empty.
- Existing `product_production_speeds` rows are reused, so this update does not require an additional database migration beyond the quantity-range migration already included in the prior update.
