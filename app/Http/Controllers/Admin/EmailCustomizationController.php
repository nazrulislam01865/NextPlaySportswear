<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmailTemplateRequest;
use App\Http\Requests\Admin\UpdateEmailGlobalBrandingRequest;
use App\Http\Requests\Admin\UpdateEmailTemplateRequest;
use App\Http\Requests\Admin\UpdateEmailVisibilityRequest;
use App\Models\EmailGlobalBranding;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Show form to create a new email template.
     */
    public function createTemplate(Request $request): View
    {
        $presets = EmailTemplate::starterPresets();
        $standardEvents = EmailTemplate::standardEvents();
        $existingTemplates = EmailTemplate::query()->orderBy('name')->get();
        $selectedPreset = $request->query('preset', 'blank');

        return view('admin.email-customization.create', [
            'presets' => $presets,
            'standardEvents' => $standardEvents,
            'existingTemplates' => $existingTemplates,
            'selectedPreset' => $selectedPreset,
        ]);
    }

    /**
     * Store a newly created email template.
     */
    public function storeTemplate(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $userId = auth('admin')->id();
        $name = trim($request->input('name'));
        $presetKey = $request->input('preset', 'blank');
        $presets = EmailTemplate::starterPresets();

        // If cloning from an existing template
        $sourceTemplate = null;
        if ($presetKey === 'clone' && $request->filled('clone_from')) {
            $sourceTemplate = EmailTemplate::where('key', $request->input('clone_from'))->first();
        }

        // Determine base key/slug
        $rawKey = $request->input('key');
        if (filled($rawKey)) {
            $baseKey = Str::slug($rawKey);
        } else {
            $baseKey = Str::slug($name);
        }

        if (empty($baseKey)) {
            $baseKey = 'custom-email-template';
        }

        // Ensure key uniqueness
        $key = $baseKey;
        $counter = 1;
        while (EmailTemplate::where('key', $key)->exists()) {
            $counter++;
            $key = "{$baseKey}-{$counter}";
        }

        // Get default preset configuration
        $presetConfig = $presets[$presetKey] ?? $presets['blank'];

        // Determine fields (request input takes precedence, then source template if cloning, then preset)
        $triggerEvent = $request->input('trigger_event')
            ?: ($sourceTemplate?->trigger_event ?? $presetConfig['trigger_event']);

        $description = $request->input('description')
            ?: ($sourceTemplate?->description ?? $presetConfig['description'] ?? "Custom email notification template for {$triggerEvent}.");

        $icon = $request->input('icon')
            ?: ($sourceTemplate?->icon ?? $presetConfig['icon'] ?? 'mail');

        $subject = $request->input('subject')
            ?: ($sourceTemplate?->subject ?? $presetConfig['subject']);

        $heading = $request->input('heading')
            ?: ($sourceTemplate?->heading ?? $presetConfig['heading']);

        $preheader = $request->input('preheader')
            ?: ($sourceTemplate?->preheader_text ?? $presetConfig['preheader_text'] ?? null);

        $intro = $request->input('intro')
            ?: ($sourceTemplate?->intro_message ?? $presetConfig['intro_message'] ?? null);

        $ctaLabel = $request->input('cta_label')
            ?: ($sourceTemplate?->cta_label ?? $presetConfig['cta_label'] ?? 'View Details');

        $ctaUrlType = $request->input('cta_url')
            ?: ($sourceTemplate?->cta_url_type ?? $presetConfig['cta_url_type'] ?? 'custom');

        $ctaCustomUrl = $request->input('cta_custom_url')
            ?: ($sourceTemplate?->cta_custom_url ?? ($presetConfig['cta_custom_url'] ?? null));

        $visibility = $sourceTemplate?->visibility_settings
            ?? $presetConfig['visibility_settings']
            ?? [
                'show_logo' => true,
                'show_greeting' => true,
                'show_cta_button' => true,
                'show_support_contact' => true,
                'show_social_links' => true,
                'show_footer_note' => true,
            ];

        $blocks = $sourceTemplate?->blocks
            ?? $presetConfig['blocks']
            ?? [];

        $sampleData = $sourceTemplate?->sample_data
            ?? $presetConfig['sample_data']
            ?? [
                'customer_name' => 'Jordan Smith',
                'customer_email' => 'jordan.smith@example.com',
                'support_email' => 'support@nextplay.com',
            ];

        $action = $request->input('action', 'draft');
        $isPublished = ($action === 'publish');

        $template = new EmailTemplate();
        $template->key = $key;
        $template->name = $name;
        $template->description = $description;
        $template->trigger_event = $triggerEvent;
        $template->icon = $icon;
        $template->status = $isPublished ? 'published' : 'draft';
        $template->active_version = 'v1.0';
        $template->draft_version = $isPublished ? null : 'v1.0-draft';
        $template->subject = $subject;
        $template->preheader_text = $preheader;
        $template->heading = $heading;
        $template->intro_message = $intro;
        $template->cta_label = $ctaLabel;
        $template->cta_url_type = $ctaUrlType;
        $template->cta_custom_url = $ctaCustomUrl;
        $template->visibility_settings = $visibility;
        $template->blocks = $blocks;
        $template->sample_data = $sampleData;
        $template->published_at = $isPublished ? now() : null;
        $template->published_by = $isPublished ? $userId : null;
        $template->updated_by = $userId;
        $template->save();

        if ($isPublished) {
            $template->createVersionSnapshot('v1.0', $userId);
        }

        return redirect()
            ->route('admin.email-customization.templates.edit', $template->key)
            ->with('status', "Template '{$template->name}' created successfully. Customize its blocks and content below.");
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
            $rawBlocks = $request->input('blocks');
            if (is_array($rawBlocks)) {
                $normalizedBlocks = [];
                foreach ($rawBlocks as $block) {
                    if (is_array($block)) {
                        $block['enabled'] = filter_var($block['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $normalizedBlocks[] = $block;
                    }
                }
                $emailTemplate->blocks = $normalizedBlocks;
            }
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
     * Delete a duplicated copy template.
     */
    public function destroyTemplate(string $template): RedirectResponse
    {
        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();

        if (! $emailTemplate->isCopy()) {
            return redirect()
                ->route('admin.email-customization.templates.index')
                ->with('error', 'Only copy templates can be deleted. Core system templates cannot be deleted.');
        }

        $name = $emailTemplate->name;
        $emailTemplate->versions()->delete();
        $emailTemplate->delete();

        return redirect()
            ->route('admin.email-customization.templates.index')
            ->with('status', "Template '{$name}' was deleted successfully.");
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
        $sampleOrders = \App\Services\Email\EmailCustomizationEngine::sampleOrders();

        return view('admin.email-customization.preview', [
            'template' => $emailTemplate,
            'branding' => $branding,
            'sampleOrders' => $sampleOrders,
        ]);
    }

    /**
     * Send test email using CentralEmailService and EmailCustomizationEngine (Phase 4).
     */
    public function sendTest(
        Request $request,
        string $template,
        \App\Services\Email\EmailCustomizationEngine $engine,
        \App\Services\Email\CentralEmailService $emailService
    ): RedirectResponse {
        $request->validate([
            'recipient_email' => ['required', 'email'],
            'sample_order' => ['nullable', 'string'],
            'use_sample_data' => ['nullable'],
            'use_published_version' => ['nullable'],
        ]);

        $emailTemplate = EmailTemplate::where('key', $template)->firstOrFail();
        $branding = EmailGlobalBranding::current();

        $sampleKey = $request->input('sample_order', 'np-12345');
        $useSample = $request->boolean('use_sample_data', true);
        $context = $useSample ? \App\Services\Email\EmailCustomizationEngine::sampleContext($sampleKey) : [];

        $recipientEmail = (string) $request->input('recipient_email');
        $message = $engine->buildEmailMessage($emailTemplate, $branding, $context, $recipientEmail);

        try {
            $emailService->sendNow($message);
            $msg = "Test email for '{$emailTemplate->name}' sent successfully to {$recipientEmail}.";
        } catch (\Throwable $e) {
            // In environments without live outbound SMTP (e.g. local dev), queue safely and inform user
            $emailService->queue($message);
            $msg = "Test email for '{$emailTemplate->name}' prepared and queued for delivery to {$recipientEmail}.";
        }

        return redirect()
            ->route('admin.email-customization.templates.preview', $emailTemplate->key)
            ->with('status', $msg);
    }
}
