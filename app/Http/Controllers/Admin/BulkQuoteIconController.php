<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BulkQuoteIcons;
use App\Support\BulkQuoteContact;
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
            'whatsappNumber' => BulkQuoteContact::whatsappNumber(),
            'customWhatsappNumber' => BulkQuoteContact::configuredNumber(),
        ]);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('storefront_branding.manage'), 403);

        $data = $request->validate([
            'whatsapp_number' => ['nullable', 'string', 'max:24', 'regex:/^\+?[0-9 \-.()]{7,24}$/'],
        ]);
        $number = trim((string) ($data['whatsapp_number'] ?? ''));
        $digits = preg_replace('/\D+/', '', $number);
        if ($number !== '' && (strlen($digits) < 7 || strlen($digits) > 15)) {
            return back()->withErrors(['whatsapp_number' => 'Enter a valid international WhatsApp number (7–15 digits).'])->withInput();
        }

        try {
            BulkQuoteContact::save($number);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['whatsapp_number' => 'Unable to save the contact number. Check storage permissions.']);
        }

        return redirect()->route('admin.bulk-quote-icons.edit')->with('status',
            $number === '' ? 'Bulk Quote WhatsApp contact reset to the storefront default.' : 'Bulk Quote WhatsApp contact updated.');
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
