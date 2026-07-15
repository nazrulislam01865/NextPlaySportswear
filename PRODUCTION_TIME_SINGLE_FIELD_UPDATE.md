# Custom Production Table: Single Production Time Field

The custom production table now uses one customer-facing **Production time** field instead of separate Min days and Max days controls.

Accepted examples:

- `7 days`
- `5-15 days`
- `5 to 15 days`
- `7`

The server parses the value and continues storing minimum and maximum day values internally so existing storefront, cart, order, and production-speed logic remains compatible.

No database migration is required.
