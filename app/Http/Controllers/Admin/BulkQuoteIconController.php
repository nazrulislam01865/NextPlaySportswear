<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BulkQuoteIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BulkQuoteIconController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth('admin')->user()?->canAdmin('storefront_branding.view'), 403);

        return view('admin.bulk-quote-icons.edit', [
            'slots' => BulkQuoteIcons::SLOTS,
            'urls' => collect(array_keys(BulkQuoteIcons::SLOTS))->mapWithKeys(
                fn (string $key): array => [$key => BulkQuoteIcons::url($key)]
            )->all(),
            'canManage' => (bool) auth('admin')->user()?->canAdmin('storefront_branding.manage'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('storefront_branding.manage'), 403);

        $rules = [];
        foreach (BulkQuoteIcons::SLOTS as $key => $_label) {
            $rules['icons.'.$key] = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,avif', 'max:2048'];
            $rules['remove.'.$key] = ['nullable', 'boolean'];
        }
        $request->validate($rules);

        $paths = BulkQuoteIcons::paths();
        $oldToDelete = [];
        $newPaths = [];
        foreach (BulkQuoteIcons::SLOTS as $key => $_label) {
            $previous = $paths[$key] ?? null;
            if ($request->hasFile('icons.'.$key)) {
                $path = $request->file('icons.'.$key)->store('storefront/bulk-quote-icons', 'public');
                $paths[$key] = $path;
                $newPaths[] = $path;
                if ($previous) {
                    $oldToDelete[] = $previous;
                }
            } elseif ($request->boolean('remove.'.$key)) {
                $paths[$key] = null;
                if ($previous) {
                    $oldToDelete[] = $previous;
                }
            }
        }

        try {
            BulkQuoteIcons::save($paths);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            report($exception);
            return back()->withErrors(['icons' => 'Unable to save the icon settings. Check storage permissions.']);
        }

        Storage::disk('public')->delete($oldToDelete);
        return redirect()->route('admin.bulk-quote-icons.edit')->with('status', 'Bulk Quote icons updated.');
    }
}
