<x-layouts.admin
    title="Email Customization Workflow"
    eyebrow="Email Customization"
    subtitle="Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step."
>
    <div class="space-y-8">
        <!-- Top Header & Callout Notice (Exact layout matching abc.png) -->
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5 font-medium">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-600 transition">⌂</a>
                    <span>›</span>
                    <span>Content</span>
                    <span>›</span>
                    <a href="{{ route('admin.email-customization.templates.index') }}" class="text-slate-500 hover:text-brand-ink transition">Email Templates</a>
                    <span>›</span>
                    <span class="text-slate-600 font-bold">Workflow</span>
                </nav>
                <h1 class="text-3xl font-black text-brand-ink tracking-tight">Email Customization Workflow</h1>
                <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                    Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step.
                </p>
            </div>

            <!-- Orange Notice Box (Matching abc.png) -->
            <div class="flex items-start gap-3.5 rounded-2xl border border-orange-200 bg-orange-50/90 p-4 lg:max-w-md shadow-sm">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">i</span>
                <div>
                    <h4 class="text-xs font-black text-orange-950">Published changes are used for the next email send.</h4>
                    <p class="mt-1 text-xs text-orange-900 leading-relaxed">
                        Any changes you make will apply to the next matching email action after you publish.
                    </p>
                </div>
            </div>
        </div>

        <!-- 9-Step Workflow Diagram (2 Rows connected with arrows matching abc.png) -->
        <div class="space-y-8">
            <!-- Row 1: Steps 1 through 5 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-stretch relative">
                <!-- Step 1: Open Email Templates -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">1</span>
                            <h3 class="text-xs font-black text-brand-ink">Open Email Templates</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            In the NextPlay admin, go to Content &gt; Email Templates.
                        </p>
                    </div>

                    <!-- Micro-mockup: Admin Dark Sidebar -->
                    <div class="rounded-xl border border-slate-700 bg-[#0B2A4A] p-3 text-white text-[10px] space-y-1.5 shadow-inner">
                        <div class="font-black text-[11px] tracking-wider mb-2">NEXT<span class="text-[#CF5D38]">PLAY</span></div>
                        <div class="flex items-center gap-1.5 text-slate-300"><span>⌂</span><span>Dashboard</span></div>
                        <div class="flex items-center gap-1.5 text-slate-300"><span>🛒</span><span>Orders</span></div>
                        <div class="flex items-center gap-1.5 text-slate-300"><span>📦</span><span>Products</span></div>
                        <div class="flex items-center gap-1.5 text-slate-300"><span>👤</span><span>Customers</span></div>
                        <div class="flex items-center gap-1.5 text-slate-300"><span>📢</span><span>Marketing</span></div>
                        <div class="pt-1 border-t border-slate-700/60">
                            <div class="flex items-center justify-between text-slate-200 font-bold">
                                <span>📄 Content</span>
                                <span class="text-[9px]">▾</span>
                            </div>
                            <div class="pl-3 pt-1">
                                <span class="flex items-center gap-1.5 text-[#CF5D38] font-black">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#CF5D38]"></span>
                                    <span>Email Templates</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Choose a template/event -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">2</span>
                            <h3 class="text-xs font-black text-brand-ink">Choose a template/event</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            Select the template you want to customize, such as Delivery Estimate Updated.
                        </p>
                    </div>

                    <!-- Micro-mockup: Template selection list -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-[10px] space-y-1.5 shadow-inner">
                        <div class="flex items-center justify-between rounded-lg border border-orange-200 bg-orange-100/60 px-2.5 py-1.5 font-bold text-orange-950">
                            <div class="flex items-center gap-1.5">
                                <span>🚚</span>
                                <span class="truncate">Delivery Estimate Updated</span>
                            </div>
                            <span>›</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <span>🛒</span>
                                <span class="truncate">Order Confirmation</span>
                            </div>
                            <span>›</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <span>📦</span>
                                <span class="truncate">Shipment Update</span>
                            </div>
                            <span>›</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <span>🔒</span>
                                <span class="truncate">Password Reset</span>
                            </div>
                            <span>›</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Edit branding, content and layout -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">3</span>
                            <h3 class="text-xs font-black text-brand-ink">Edit branding, content and layout</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            Update your logo, colors, messaging, and layout.
                        </p>
                    </div>

                    <!-- Micro-mockup: Mini email editor canvas -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-2 shadow-inner grid grid-cols-[1.2fr_0.8fr] gap-1.5 items-center">
                        <div class="rounded border border-slate-200 bg-white p-1.5 space-y-1">
                            <div class="text-[8px] font-black text-center text-brand-ink">NEXT<span class="text-[#CF5D38]">PLAY</span></div>
                            <div class="h-6 w-full rounded bg-slate-100 flex items-center justify-center text-slate-400 text-[8px]">🖼</div>
                            <div class="h-1.5 w-3/4 rounded bg-slate-200"></div>
                            <div class="h-1.5 w-1/2 rounded bg-slate-200"></div>
                            <div class="h-2 w-full rounded bg-[#CF5D38]"></div>
                        </div>
                        <div class="space-y-1 text-[8px]">
                            <div class="rounded border border-slate-200 bg-white p-1 font-bold text-slate-700 flex items-center gap-1"><span>🖼</span> Logo</div>
                            <div class="rounded border border-slate-200 bg-white p-1 font-bold text-slate-700 flex items-center gap-1"><span>Aa</span> Text</div>
                            <div class="rounded border border-slate-200 bg-white p-1 font-bold text-slate-700 flex items-center gap-1"><span>⊞</span> Layout</div>
                            <div class="rounded border border-slate-200 bg-white p-1 font-bold text-slate-700 flex items-center gap-1"><span>🎨</span> Colors</div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Choose what to show or hide -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">4</span>
                            <h3 class="text-xs font-black text-brand-ink">Choose what to show or hide</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            Control which content blocks and details appear in the email.
                        </p>
                    </div>

                    <!-- Micro-mockup: Toggles list -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-[9px] space-y-1 shadow-inner">
                        <div class="flex items-center justify-between rounded bg-white p-1 border border-slate-100">
                            <span class="text-slate-700 font-semibold truncate">Order details</span>
                            <span class="h-3 w-5 rounded-full bg-[#CF5D38] inline-flex items-center justify-end px-0.5"><span class="h-2 w-2 rounded-full bg-white"></span></span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-white p-1 border border-slate-100">
                            <span class="text-slate-700 font-semibold truncate">Tracking information</span>
                            <span class="h-3 w-5 rounded-full bg-[#CF5D38] inline-flex items-center justify-end px-0.5"><span class="h-2 w-2 rounded-full bg-white"></span></span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-white p-1 border border-slate-100">
                            <span class="text-slate-400 truncate">Promotional banner</span>
                            <span class="h-3 w-5 rounded-full bg-slate-300 inline-flex items-center justify-start px-0.5"><span class="h-2 w-2 rounded-full bg-white"></span></span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-white p-1 border border-slate-100">
                            <span class="text-slate-400 truncate">Referral section</span>
                            <span class="h-3 w-5 rounded-full bg-slate-300 inline-flex items-center justify-start px-0.5"><span class="h-2 w-2 rounded-full bg-white"></span></span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-white p-1 border border-slate-100">
                            <span class="text-slate-700 font-semibold truncate">Help &amp; support</span>
                            <span class="h-3 w-5 rounded-full bg-[#CF5D38] inline-flex items-center justify-end px-0.5"><span class="h-2 w-2 rounded-full bg-white"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Preview with sample data -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">5</span>
                            <h3 class="text-xs font-black text-brand-ink">Preview with sample data</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            See how the email will look for your customers using sample data.
                        </p>
                    </div>

                    <!-- Micro-mockup: Mini Desktop Preview -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-2 shadow-inner">
                        <div class="rounded-lg border border-slate-200 bg-white p-2 text-center space-y-1.5 shadow-sm">
                            <div class="text-[9px] font-black text-brand-ink">NEXT<span class="text-[#CF5D38]">PLAY</span></div>
                            <div class="py-1">
                                <span class="text-base">🚚</span>
                            </div>
                            <div class="h-1.5 w-4/5 mx-auto rounded bg-slate-200"></div>
                            <div class="h-1 w-3/5 mx-auto rounded bg-slate-200"></div>
                            <div class="h-2.5 w-16 mx-auto rounded bg-[#CF5D38]"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Path connector to row 2 -->
            <div class="hidden lg:flex justify-end pr-12 -my-3 text-slate-300">
                <svg class="h-8 w-16" fill="none" viewBox="0 0 64 32">
                    <path d="M10 2 Q 54 2 54 16 T 10 30" stroke="#CBD5E1" stroke-width="2" stroke-dasharray="4 4"/>
                </svg>
            </div>

            <!-- Row 2: Steps 6 through 9 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                <!-- Step 6: Save draft or Publish changes -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">6</span>
                            <h3 class="text-xs font-black text-brand-ink">Save draft or Publish changes</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            Save a draft to keep working, or publish to make changes live for the next email send.
                        </p>
                    </div>

                    <!-- Micro-mockup: Dual Buttons -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2 shadow-inner">
                        <button type="button" class="w-full rounded-lg border border-slate-300 bg-white py-1.5 text-[10px] font-bold text-slate-700 shadow-sm flex items-center justify-center gap-1.5">
                            <span>📄</span>
                            <span>Save Draft</span>
                        </button>
                        <button type="button" class="w-full rounded-lg py-1.5 text-[10px] font-black text-white shadow-sm flex items-center justify-center gap-1.5" style="background-color: #CF5D38 !important;">
                            <span>✈</span>
                            <span>Publish Changes</span>
                        </button>
                    </div>
                </div>

                <!-- Step 7: Next matching event uses latest published version -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">7</span>
                            <h3 class="text-xs font-black text-brand-ink">Next matching event uses latest published version</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            When the next matching event occurs (e.g., Delivery Estimate Updated), the system loads the latest published version.
                        </p>
                    </div>

                    <!-- Micro-mockup: Database Stack with sync badge -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-center shadow-inner relative">
                        <div class="relative flex flex-col items-center">
                            <!-- Cylinder Database Layers -->
                            <div class="w-16 h-5 rounded-t-full bg-slate-300 border border-slate-400"></div>
                            <div class="w-16 h-4 -mt-1 bg-slate-200 border-x border-b border-slate-400"></div>
                            <div class="w-16 h-4 -mt-1 rounded-b-lg bg-slate-300 border-x border-b border-slate-400"></div>
                            <!-- Circular Orange Sync Badge -->
                            <div class="absolute -bottom-1 -right-2 grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-white text-[11px] font-bold shadow-md">
                                ↻
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 8: Email is sent to customer -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">8</span>
                            <h3 class="text-xs font-black text-brand-ink">Email is sent to customer</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            The system sends the email using the latest published template version.
                        </p>
                    </div>

                    <!-- Micro-mockup: Envelope with Paper Airplane Badge -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-center shadow-inner relative">
                        <div class="relative">
                            <!-- Envelope -->
                            <div class="w-20 h-12 rounded-lg bg-slate-200 border border-slate-300 relative flex items-center justify-center shadow-sm">
                                <div class="absolute inset-0 flex items-center justify-center text-slate-400 text-lg">✉</div>
                            </div>
                            <!-- Flying Paper Plane Badge -->
                            <div class="absolute -top-2 -right-2 grid h-7 w-7 place-items-center rounded-full bg-[#CF5D38] text-white text-xs font-black shadow-md">
                                ✈
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 9: Customer sees the updated design and content -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#CF5D38] text-xs font-black text-white">9</span>
                            <h3 class="text-xs font-black text-brand-ink">Customer sees updated design and content</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                            Your customer receives the email with the new branding, content, and layout.
                        </p>
                    </div>

                    <!-- Micro-mockup: Display with Burst Rays -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center justify-center shadow-inner relative">
                        <div class="relative p-2">
                            <!-- Sparkle / Burst Rays -->
                            <div class="absolute -top-1 -left-1 text-[#CF5D38] text-xs font-black">╲</div>
                            <div class="absolute -top-2 left-1/2 -translate-x-1/2 text-[#CF5D38] text-xs font-black">│</div>
                            <div class="absolute -top-1 -right-1 text-[#CF5D38] text-xs font-black">╱</div>

                            <!-- Screen Device -->
                            <div class="w-24 rounded-lg border border-slate-300 bg-white p-1.5 text-center shadow-sm">
                                <div class="text-[8px] font-black text-brand-ink">NEXT<span class="text-[#CF5D38]">PLAY</span></div>
                                <div class="my-0.5 text-xs">🚚</div>
                                <div class="h-1 w-3/4 mx-auto rounded bg-slate-200"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Version Status Bottom Bar (Exact match of abc.png) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h4 class="text-xs font-black text-brand-ink">Version Status</h4>
                <p class="text-[11px] text-slate-500">Templates can be in a draft or published state.</p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <!-- Draft Status Pill & Explanation -->
                <div class="flex items-start sm:items-center gap-3">
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-800 shrink-0">Draft</span>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <strong class="font-bold text-slate-800">Draft:</strong> Changes are saved but not live. The current published version continues to be used for email sends.
                    </p>
                </div>

                <!-- Published Status Pill & Explanation -->
                <div class="flex items-start sm:items-center gap-3">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800 shrink-0">Published</span>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <strong class="font-bold text-slate-800">Published:</strong> Active version used for the next matching email send.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
