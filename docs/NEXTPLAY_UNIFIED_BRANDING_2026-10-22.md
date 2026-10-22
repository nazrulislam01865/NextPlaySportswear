# NextPlay Sportswear unified branding — October 22, 2026

## Scope
These **two updated Laravel source projects** use NextPlay as the single source of truth for the operations portal's public identity. Existing order, inquiry, artwork, approval, task, and quotation logic is not rewritten.

- Default brand colors from the NPS `resources/css/storefront-theme.css`: navy `#061F44`, orange `#CF5D38`.
- FlowTracker's central CSS settings, common action buttons, sidebar, form typography, login, browser title and favicons consume the NextPlay identity. Workflow status, warning, failure, and priority colors retain their semantic meaning.
- NPS exposes an authenticated brand snapshot; its admin logo/favicon save sends the snapshot to the operations portal. Hourly scheduled reconciliation handles changes made outside the NPS admin interface, **if the NPS scheduler is running**.
- FlowTracker persists one validated branding snapshot under its existing local storage disk and caches it for 60 seconds. **No HTTP request to NPS and no cross-project DB query is performed on normal FlowTracker page loads.** Branding can be pulled manually if either server was offline.
- Local FlowTracker branding editing is disabled while NPS synchronization is enabled; the previous implementation is preserved for rollback.
- Visible names in core shell, RFQ views, screen explanations, and report export metadata are updated to NextPlay. Internal `FlowTrack` class names, routes, DB fields, and script identifiers must remain intact to preserve integration behavior.

## Installation
1. Back up the existing FlowTracker and NPS project directories and their databases. Preserve **each project's existing `.env`, storage directory, APP_KEY, and user uploads**. Apply each updated source ZIP to its corresponding project. Do not swap projects.
2. Ensure the existing tokens match:
   - FlowTracker `.env`: `NEXTPLAY_SOURCE_BASE_URL=http://127.0.0.1:8000`, `NEXTPLAY_INTEGRATION_TOKEN=<same-server-secret>`, `NEXTPLAY_BRANDING_SYNC_ENABLED=true`.
   - NextPlay `.env`: `FLOWTRACK_BASE_URL=http://127.0.0.1:8001`, `FLOWTRACK_INTEGRATION_TOKEN=<same-server-secret>`, `FLOWTRACK_INTEGRATION_ENABLED=true`.
   - `APP_URL` of NextPlay should be publicly reachable by browser clients and **match the origin** configured in FlowTracker's `NEXTPLAY_SOURCE_BASE_URL`. For local tests use `http://127.0.0.1:8000` consistently.
3. In each project, run `npm ci && npm run build && php artisan optimize:clear` (after applying to your existing Laravel installations). No new database migration is required.
4. Start the normal **single-command** local launcher in FlowTracker: `bash scripts/run-nextplay-local.sh`.
5. When both servers are running, initialize the snapshot once:
   `cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo && php artisan nextplay:sync-branding`
6. To force resynchronization after changing NPS logo/title/settings:
   - In NPS: `php artisan flowtrack:push-branding`
   - Or in FlowTracker: `php artisan nextplay:sync-branding`
7. Refresh login, Dashboard, Create Order, Order Details, Master Data, Reports, and RFQ. Test NPS artwork approval and bulk quotation separately to confirm integration still works.

## Font assets and scope limitations
The supplied NPS archive contains the **Expose CSS font declarations** but **does not contain the Expose `.woff2` font files**. No font binaries are copied or redistributed in these ZIPs. The FlowTracker CSS requests the declared font URL from the running NextPlay site and falls back to a system sans-serif if the font is unavailable or blocked by cross-origin font rules. Configure a correctly licensed, accessible font source on NPS to obtain the precise same face on both origins; font CORS headers may be needed.

NPS's storefront title is controlled by `config('storefront.name')` / `STOREFRONT_NAME`. Changing that env/config requires a config reload plus the branding sync (or the running scheduler's hourly reconciliation). NPS admin uploads of logo/favicon publish immediately, with manual retry on connection failure. Colors are read from NPS's central stylesheet when a snapshot is published; stylesheet deployments should trigger a push or wait for reconciliation.

The NPS frontend and FlowTracker have different workflows and layouts. This release unifies the **brand foundation** without replacing approved operational screen structures with consumer storefront templates. Complex, prototype-specific colors outside common theme tokens and visual detail require browser screenshot QA. No live two-application integration tests were available in this packaging environment.

## Security / performance
- Authenticated HTTPS or local bearer-token integration; no public branding mutation endpoint.
- Asset URL origin validated against configured NPS source; change the configured origin if using a distinct verified CDN rather than accepting arbitrary third-party URLs.
- Does not expose either server's integration token to the browser.
- Reuses existing authentication middleware and caching; does not introduce cross-database coupling.

## Rollback
Restore both backed-up code directories (including matching frontend builds), keep the unchanged database/storage, and run `php artisan optimize:clear`. If you only need to disable the new display branding on a test instance temporarily, set FlowTracker `NEXTPLAY_BRANDING_SYNC_ENABLED=false`, clear config cache, and restore the former branding view settings. The prior local workspace-asset editing services have not been removed.

## Checks completed in packaging environment
PHP lint on all changed `.php` files, PostCSS parsing for all modified CSS files, and both ZIP archives' structural integrity. Composer packages and Node dependencies are absent from uploaded source projects; full Laravel test suite, Vite production build, browser screenshots, and live branded API handoff still need local testing before production.
