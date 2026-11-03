# NextPlay full-suite regression: batch triage (03 Nov 2026)

## What triggered the repeated failures

The payment Phase 1, 2, and 3 targeted suites passed locally according to the supplied terminal output. The full suite also includes older and independently evolving website/admin tests. `--stop-on-failure` then halts at the first stale assertion, requiring another round-trip for every mismatch. That is not an efficient release gate.

## Confirmed batch corrections in this archive

- `tests/Unit/ProductCustomizationMenuRegistryTest.php`: expect the existing `1.40 Personalization Templates` entry after `1.39 Design Styles` (no menu behavior changed).
- `tests/Feature/Admin/PersonalizationCategoryIndexTest.php`: include required category columns in test fixtures (no category schema change).
- `tests/Feature/Admin/ShippingDeliveryPageValidationTest.php`: enable `web` middleware on the test-only request endpoint, matching actual admin session-based validation.
- `tests/Feature/Admin/MenuManagementTest.php`: align the test expectation with the existing explicit decision in `NavigationService::normalizeHeaderPayload()` to retire the legacy `how-to-order` header entry (no navigation behavior changed).
- `app/Services/Security/SafeHtmlService.php` and `tests/Unit/SafeHtmlServiceTest.php`: correct an actual link sanitizer bug where a valid `target="_blank"` was removed without adding the required `rel="noopener noreferrer"`, and support safe URLs supplied in single-quoted href attributes. Invalid executable URL schemes continue to be removed. Test coverage was expanded.

## Other failures reported by the earlier full-suite output

These have **not** been marked resolved without isolated failure traces and runtime verification:

- About page managed-content/editor tests: static inspection shows the current redesigned visible About page is not consistently using the stored CMS values; old semantic markup is hidden. Needs a separate UI/CMS content-binding review, not assertion weakening.
- Bulk quote Word export tests: requires actual document-rendering runtime/environment analysis.
- Email customization styling and send-test tests: need assertion trace and queued mail execution context.
- Media upload validation tests: current endpoint has an explicit 20-file limit and separate image-picker validation; detailed response errors are needed to identify the real mismatch.
- Authentication separation: do **not** relax admin/customer guard rules to satisfy tests; inspect the actual status and session state.
- Shipping Delivery page management: requires isolated trace; the validation-test middleware issue is corrected separately.
- Other tests beyond the previous fatal memory limit were not executed in that earlier full-suite run.

## One complete diagnostic run (not --stop-on-failure)

From the project root on the local Mac, run the suite **once** with enough CLI memory and save the complete report:

```sh
php -d memory_limit=512M artisan test --no-ansi > /tmp/nextplay-full-suite.log 2>&1
```

The command may return nonzero when any tests fail. That is expected; keep the full report. To see the failures without scrolling through every passing test:

```sh
grep -E 'FAIL  |FAILED  |Tests:|Fatal error|Premature end' /tmp/nextplay-full-suite.log
```

Upload `/tmp/nextplay-full-suite.log` for a **single grouped remediation pass**. If the process exits because of memory before executing all test classes, run class-level isolation as a separate diagnostic rather than repeatedly rerunning `--stop-on-failure`.

## Release policy

Do not deploy new changes on static checks alone. Rerun the four payment suites, run the suite without stop-on-failure, and verify Stripe test-mode flows before promotion. The attached archive does not modify payment orchestration, refund processing, the admin financial lock, or the database schema. No database migration is needed.

## Verification boundaries

The submitted zip excludes Composer `vendor` and this container lacks the PHP SQLite/mbstring/GD extensions used by this project; local Laravel PHPUnit execution is unavailable here. PHP lint, source-to-archive comparison, archive integrity, and stand-alone sanitizer behavior checks are possible and were performed. Full green status is **not claimed**.
