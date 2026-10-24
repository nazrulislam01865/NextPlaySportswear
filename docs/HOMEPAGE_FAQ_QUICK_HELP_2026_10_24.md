# Homepage FAQ — Quick Help (24 Oct 2026)

## Change
- Filled the FAQ's existing reserved right-hand column with the four-card **Quick Help** design from the supplied reference.
- Existing FAQ questions, answers, accordion interactions and ordering are unchanged.
- Reused the storefront section-heading component, theme fonts and theme color variables.
- Added reusable Quick Help card and SVG icon components, a responsive two-column card grid, and proportional FAQ/Quick Help desktop columns. On narrower viewports the panel stacks below the FAQ.
- Default cards: Size Guide -> /size-guide, Artwork Guidelines -> /artwork-guidelines, Track Your Order -> /track-order, Contact Us -> /contact-us. These URLs are existing routes.

## Admin configuration
Navigate to **Admin > Homepage Sections > FAQ > Edit Section > Quick Help**.
- Edit the Quick Help title and subtitle; show/hide the entire panel.
- Each of the four cards has an editable title, description, destination URL, visibility, and a standard SVG icon selector.
- Optional uploaded PNG/WebP/JPEG icon per card (recommended transparent 256 x 256); remove an upload to return to the selected standard icon.
- The pre-existing FAQ question editor remains on the same admin page.

## Storage and performance
- Quick Help settings use the existing `homepage_sections.settings` JSON field for the FAQ row; **no migration or extra queries**.
- New uploads use existing `StoredPublicImage` / `PublicMedia` helpers; old uploaded icons are retained until replacement or explicit removal.
- Existing homepage section cache is invalidated by the existing save controller.
- Server-validated URLs use the existing SafePublicUrl rule.

## Deployment
- Upload/update the full project. The `public/build` manifest and prebuilt storefront CSS already contain the Quick Help styles; no npm command is required just for these changes.
- Run `php artisan optimize:clear` and `php artisan view:clear` after deployment.
- No database migration or seeding is required. Stored FAQ settings from existing installations are merged with the Quick Help defaults until an admin saves changes.
- When rebuilding assets on a normal development machine, run `npm ci && npm run build`.

## Verification
- PHP syntax checks passed for modified PHP classes.
- `php tests/Regression/HomeQuickHelpAdminAndLayoutTest.php` passed.
- Existing HomeSectionHeadingAlignmentTest, BestSellingGearBackgroundCustomizationTest and HomepageTemporarilyHiddenSectionsTest passed.
- Vite rebuild and live-database/browser QA were not possible in the supplied environment. The updated versioned prebuilt stylesheet is included.
