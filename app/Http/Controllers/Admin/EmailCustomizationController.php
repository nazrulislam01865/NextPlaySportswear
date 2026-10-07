<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailGlobalBrandingRequest;
use App\Http\Requests\Admin\UpdateEmailTemplateRequest;
use App\Http\Requests\Admin\UpdateEmailVisibilityRequest;
use App\Models\EmailGlobalBranding;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmailCustomizationController extends Controller
{
    /**
     * List transactional email templates (Image: def.png).
     */
    public function templates(Request $request): View
    {
        $statusFilter = $request->query('status');
        $eventFilter = $request->query('event');
        $search = trim((string) $request->query('q', ''));

        $query = EmailTemplate::query();

        if ($statusFilter) {
            $query->where('status', strtolower($statusFilter));
        }

        if ($eventFilter && $eventFilter !== 'all') {
            $query->where('trigger_event', $eventFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trigger_event', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('name')->get();

        return view('admin.email-customization.templates', [
            'templates' => $templates,
            'currentSearch' => $search,
            'currentStatus' => $statusFilter,
            'currentEvent' => $eventFilter,
        ]);
    }

    /**
     * Global Branding Settings editor (Image: ghi.png).
     */
    public function branding(): View
    {
        $branding = EmailGlobalBranding::current();

        return view('admin.email-customization.branding', [
            'branding' => $branding,
        ]);
    }

    /**
     * Process Global Branding Settings update (Phase 3).
     */
    public function updateBranding(UpdateEmailGlobalBrandingRequest $request): RedirectResponse
    {
        $branding = EmailGlobalBranding::current();

        // Logo handling
        if ($request->hasFile('logo')) {
            if ($branding->logo_path && Storage::disk('public')->exists($branding->logo_path)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $branding->logo_path = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($branding->logo_path && Storage::disk('public')->exists($branding->logo_path)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $branding->logo_path = null;
        }

        $branding->header_bg_color = $request->input('header_bg_color', '#0B2A4A');
        $branding->button_color = $request->input('button_color', '#F15A2B');
        $branding->font_family = $request->input('font_family', 'Inter');
        $branding->footer_text = $request->input('footer_text');
        $branding->support_email = $request->input('support_email', 'support@nextplay.com');
        $branding->support_phone = $request->input('support_phone');

        $branding->social_links = [
            'facebook' => $request->input('social_facebook'),
            'instagram' => $request->input('social_instagram'),
            'twitter' => $request->input('social_twitter'),
            'youtube' => $request->input('social_youtube'),
        ];

        $branding->is_published = ($request->input('action') === 'publish');
        $branding->updated_by = auth('admin')->id();
        $branding->save();

        $actionText = $branding->is_published ? 'saved and published' : 'saved as draft';

        return redirect()
            ->route('admin.email-customization.branding.edit')
            ->with('status', "Global branding settings {$actionText} successfully.");
    }

    /**
     * Visual 9-Step Workflow Guide (Image: abc.png).
     */
    public function workflow(): View
    {
        return view('admin.email-customization.workflow');
    }

    /**
     * Template Content & Blocks Editor (Image: pqr.png).
     */
    public function editTemplate(string $template): View
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();
        $branding = EmailGlobalBranding::current();

        return view('admin.email-customization.edit', [
            'template' => $emailTemplate,
            'branding' => $branding,
        ]);
    }

    /**
     * Process Template Content & Blocks update (Phase 3).
     */
    public function updateTemplate(UpdateEmailTemplateRequest $request, string $template): RedirectResponse
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();

        $action = $request->input('action', 'draft');
        $userId = auth('admin')->id();

        $emailTemplate->subject = $request->input('subject');
        $emailTemplate->preheader_text = $request->input('preheader');
        $emailTemplate->heading = $request->input('heading');
        $emailTemplate->intro_message = $request->input('intro');
        $emailTemplate->cta_label = $request->input('cta_label');
        $emailTemplate->cta_url_type = $request->input('cta_url', 'Order Details Page');
        $emailTemplate->cta_custom_url = $request->input('cta_custom_url');

        if ($request->has('blocks')) {
            $emailTemplate->blocks = $request->input('blocks');
        }

        $emailTemplate->updated_by = $userId;

        if ($action === 'publish') {
            $nextVersion = $emailTemplate->nextVersion();
            $emailTemplate->active_version = $nextVersion;
            $emailTemplate->draft_version = null;
            $emailTemplate->status = 'published';
            $emailTemplate->published_at = now();
            $emailTemplate->published_by = $userId;
            $emailTemplate->save();

            // Create immutable version snapshot
            $emailTemplate->createVersionSnapshot($nextVersion, $userId);

            return redirect()
                ->route('admin.email-customization.templates.edit', $emailTemplate->key)
                ->with('status', "Template published successfully as version {$nextVersion}.");
        }

        // Save Draft
        $emailTemplate->status = 'draft';
        $emailTemplate->draft_version = $emailTemplate->nextVersion() . '-draft';
        $emailTemplate->save();

        return redirect()
            ->route('admin.email-customization.templates.edit', $emailTemplate->key)
            ->with('status', 'Template draft saved successfully.');
    }

    /**
     * Duplicate an existing template (Phase 3).
     */
    public function duplicateTemplate(string $template): RedirectResponse
    {
        $source = EmailTemplate::where('key', $template)->firstOrFail();

        $counter = 1;
        $baseKey = $source->key . '-copy';
        $newKey = $baseKey;

        while (EmailTemplate::where('key', $newKey)->exists()) {
            $counter++;
            $newKey = "{$baseKey}-{$counter}";
        }

        $clone = $source->replicate([
            'created_at',
            'updated_at',
        ]);

        $clone->key = $newKey;
        $clone->name = $source->name . ' (Copy)';
        $clone->status = 'draft';
        $clone->active_version = 'v1.0';
        $clone->draft_version = 'v1.0-draft';
        $clone->published_at = null;
        $clone->published_by = null;
        $clone->updated_by = auth('admin')->id();
        $clone->save();

        return redirect()
            ->route('admin.email-customization.templates.edit', $clone->key)
            ->with('status', "Template duplicated successfully as '{$clone->name}'.");
    }

    /**
     * Content Visibility Toggles (Image: mno.png).
     */
    public function visibility(string $template): View
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();
        $branding = EmailGlobalBranding::current();

        return view('admin.email-customization.visibility', [
            'template' => $emailTemplate,
            'branding' => $branding,
        ]);
    }

    /**
     * Process Content Visibility update (Phase 3).
     */
    public function updateVisibility(UpdateEmailVisibilityRequest $request, string $template): RedirectResponse
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();

        $visibilitySettings = [
            'show_logo' => $request->boolean('show_logo'),
            'show_greeting' => $request->boolean('show_greeting'),
            'show_previous_estimate' => $request->boolean('show_previous_estimate'),
            'show_updated_estimate' => $request->boolean('show_updated_estimate'),
            'show_holiday_reason' => $request->boolean('show_holiday_reason'),
            'show_delivery_card' => $request->boolean('show_delivery_card'),
            'show_order_number' => $request->boolean('show_order_number'),
            'show_cta_button' => $request->boolean('show_cta_button'),
            'show_support_contact' => $request->boolean('show_support_contact'),
            'show_social_links' => $request->boolean('show_social_links'),
            'show_footer_note' => $request->boolean('show_footer_note'),
        ];

        $emailTemplate->visibility_settings = $visibilitySettings;
        $emailTemplate->updated_by = auth('admin')->id();

        $action = $request->input('action', 'draft');

        if ($action === 'publish') {
            $nextVersion = $emailTemplate->nextVersion();
            $emailTemplate->active_version = $nextVersion;
            $emailTemplate->draft_version = null;
            $emailTemplate->status = 'published';
            $emailTemplate->published_at = now();
            $emailTemplate->published_by = auth('admin')->id();
            $emailTemplate->save();

            $emailTemplate->createVersionSnapshot($nextVersion, auth('admin')->id());

            return redirect()
                ->route('admin.email-customization.templates.visibility', $emailTemplate->key)
                ->with('status', "Visibility settings published successfully as version {$nextVersion}.");
        }

        $emailTemplate->status = 'draft';
        $emailTemplate->draft_version = $emailTemplate->nextVersion() . '-draft';
        $emailTemplate->save();

        return redirect()
            ->route('admin.email-customization.templates.visibility', $emailTemplate->key)
            ->with('status', 'Visibility settings draft saved successfully.');
    }

    /**
     * Preview & Send Test Email (Image: jkl.png, stu.png).
     */
    public function preview(string $template): View
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();
        $branding = EmailGlobalBranding::current();

        return view('admin.email-customization.preview', [
            'template' => $emailTemplate,
            'branding' => $branding,
        ]);
    }

    /**
     * Send test email (Phase 3 / Phase 4 dispatcher stub).
     */
    public function sendTest(Request $request, string $template): RedirectResponse
    {
        $request->validate([
            'recipient_email' => ['required', 'email'],
        ]);

        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();
        $recipient = $request->input('recipient_email');

        return redirect()
            ->route('admin.email-customization.templates.preview', $emailTemplate->key)
            ->with('status', "Test email for '{$emailTemplate->name}' queued to {$recipient}.");
    }
}
