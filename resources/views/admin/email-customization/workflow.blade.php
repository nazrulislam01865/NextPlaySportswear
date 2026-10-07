<x-layouts.admin
    title="Email Customization Workflow"
    eyebrow="Email Customization"
    subtitle="Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step."
>
    <div class="space-y-6">
        <!-- Breadcrumb & Top Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="hover:text-slate-700">Email Templates</a>
                <span>/</span>
                <span class="text-brand-ink">Workflow Guide</span>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.email-customization.templates.index') }}" class="btn btn-primary text-xs">
                    Open Email Templates →
                </a>
            </div>
        </div>

        <!-- Banner -->
        <div class="flex items-start justify-between gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-5 text-xs font-bold text-orange-900 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
                <div>
                    <h4 class="text-sm font-extrabold text-orange-950">Published changes are used for the next email send.</h4>
                    <p class="mt-1 font-semibold leading-relaxed text-orange-800">Any changes you make will apply to the next matching email action immediately after you hit publish.</p>
                </div>
            </div>
        </div>

        <!-- 9-Step Workflow Diagram Grid -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-card sm:p-8">
            <h3 class="text-lg font-black text-brand-ink">How Email Customization Works</h3>
            <p class="mt-1 text-xs text-slate-500">From editing branding and content to sending live transactional emails to your customers.</p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Step 1 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">1</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Open Email Templates</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">In the NextPlay admin, navigate to <strong>Store > Email Customization</strong> to see all available transactional templates.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">2</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Choose a Template</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Select any transactional event to customize (e.g., Order Confirmation, Delivery Estimate Updated, Shipment Update, Welcome Email).</p>
                </div>

                <!-- Step 3 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">3</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Edit Branding & Content</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Update logo, colors, heading, intro text, CTA buttons, and personalize messaging with dynamic variables like <code>@{{customer_name}}</code>.</p>
                </div>

                <!-- Step 4 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">4</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Choose What to Show</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Use content visibility toggles to control which sections (order details, tracking cards, social links, support notes) appear in the email.</p>
                </div>

                <!-- Step 5 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">5</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Preview with Sample Data</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Switch between Desktop and Mobile views. Choose sample orders to inspect real personalization before publishing.</p>
                </div>

                <!-- Step 6 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">6</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Save Draft or Publish</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Save drafts to keep refining without affecting customers, or publish when ready to increment the active template version.</p>
                </div>

                <!-- Step 7 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">7</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Event Trigger Resolves</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">When the event happens in NextPlay (e.g., customer places order or delivery date changes), the engine loads the latest published template.</p>
                </div>

                <!-- Step 8 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">8</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Email Sent to Customer</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">The transactional email service compiles the HTML with customer data and branding, then delivers it safely.</p>
                </div>

                <!-- Step 9 -->
                <div class="relative rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#F15A2B] text-xs font-black text-white" style="background-color: #F15A2B !important; color: #ffffff !important;">9</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Customer Sees Clean Email</h4>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-600">Your customer receives the email with flawless branding, responsive layout, and up-to-date order information.</p>
                </div>
            </div>

            <!-- Version Status Legend -->
            <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-xs font-black uppercase tracking-wider text-brand-ink">Version Status Types</h4>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-800">Draft</span>
                        <p class="text-xs leading-relaxed text-slate-600">Changes are saved safely in your editor but not live. The current published version continues to be sent to customers.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800">Published</span>
                        <p class="text-xs leading-relaxed text-slate-600">Active version used immediately for the next matching customer email send.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
