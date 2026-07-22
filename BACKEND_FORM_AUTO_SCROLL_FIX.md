# Backend Product Form Auto Scroll Fix

Updated the admin product form stepper behavior so it no longer automatically scrolls to the next section when the admin stops typing.

Changes:
- Disabled auto-advance in `resources/js/app.js`.
- Kept manual step navigation working when clicking the stepper tabs.
- Kept progress/checklist updates working without moving the page.
- Updated `resources/views/admin/products/_form.blade.php` to call progress updates without auto-advance.
- Patched the built Vite JavaScript asset in `public/build/assets/app-BprCRB3E.js` so the fix works immediately without rebuilding.
