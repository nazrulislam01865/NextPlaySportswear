<x-layouts.admin
    title="Workflow Guide"
    eyebrow="Email Customization"
    subtitle="Step-by-step lifecycle from editing templates to customer email delivery."
>
    <div class="space-y-6 pb-12">
        <!-- Top Action Bar -->
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.email-customization.templates.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>All Templates</span>
            </a>
            <a href="{{ route('admin.email-customization.branding.edit') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 5 5 0 015-5h4a2 2 0 012 2v4a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                <span>Global Branding</span>
            </a>
        </div>

        <!-- Main Workflow Poster Canvas (Matches blueprint abc.png exactly) -->
        <div class="wf-canvas">

            <!-- Top Header & Info Notice -->
            <div class="wf-header">
                <div class="wf-title-block">
                    <div class="wf-logo">
                        <span>NEXT</span><span class="wf-logo-orange">PLAY</span>
                    </div>
                    <h2 class="wf-main-title">Email Customization Workflow</h2>
                    <p class="wf-main-subtitle">
                        Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step.
                    </p>
                </div>

                <!-- Orange Notice Callout -->
                <div class="wf-notice-box">
                    <div class="wf-notice-icon">i</div>
                    <div class="wf-notice-content">
                        <div class="wf-notice-heading">Published changes are used for the next email send.</div>
                        <div class="wf-notice-text">Any changes you make will apply to the next matching email action after you publish.</div>
                    </div>
                </div>
            </div>

            <!-- Workflow Visual Diagram -->
            <div class="wf-diagram-scroll">
                <div class="wf-diagram-inner">

                    <!-- ROW 1: Steps 1 through 5 -->
                    <div class="wf-row wf-row-1">

                        <!-- Step 1: Open Email Templates -->
                        <div class="wf-card">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">1</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Open Email Templates</h3>
                                    <p class="wf-card-desc">In the NextPlay admin, go to Content &gt; Email Templates.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-sidebar">
                                <div class="wf-sb-logo">NEXT<span class="wf-logo-orange">PLAY</span></div>
                                <div class="wf-sb-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    <span>Dashboard</span>
                                </div>
                                <div class="wf-sb-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span>Orders</span>
                                </div>
                                <div class="wf-sb-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span>Products</span>
                                </div>
                                <div class="wf-sb-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span>Customers</span>
                                </div>
                                <div class="wf-sb-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                    <span>Marketing</span>
                                </div>
                                <div class="wf-sb-group">
                                    <div class="wf-sb-group-title">
                                        <div class="wf-flex-center gap-1">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#F15A2B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Content</span>
                                        </div>
                                        <span>▾</span>
                                    </div>
                                    <div class="wf-sb-subitem wf-sb-subitem-active">
                                        <span class="wf-dot-orange"></span>
                                        <span>Email Templates</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 1 -> 2 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 2: Choose a template/event -->
                        <div class="wf-card">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">2</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Choose a template/event</h3>
                                    <p class="wf-card-desc">Select the template you want to customize, such as Delivery Estimate Updated.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-list">
                                <div class="wf-template-item wf-template-active">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-icon-truck" viewBox="0 0 24 24" fill="none" stroke="#CF5D38" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 3v5a1 1 0 01-1 1h-1m-4 0h-1"/></svg>
                                        <span class="truncate">Delivery Estimate Updated</span>
                                    </div>
                                    <span class="wf-chevron">›</span>
                                </div>
                                <div class="wf-template-item">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span class="truncate">Order Confirmation</span>
                                    </div>
                                    <span class="wf-chevron">›</span>
                                </div>
                                <div class="wf-template-item">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span class="truncate">Shipment Update</span>
                                    </div>
                                    <span class="wf-chevron">›</span>
                                </div>
                                <div class="wf-template-item">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="truncate">Password Reset</span>
                                    </div>
                                    <span class="wf-chevron">›</span>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 2 -> 3 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 3: Edit branding, content and layout -->
                        <div class="wf-card">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">3</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Edit branding, content and layout</h3>
                                    <p class="wf-card-desc">Update your logo, colors, messaging, and layout.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-editor">
                                <!-- Left mini preview -->
                                <div class="wf-editor-preview">
                                    <div class="wf-mini-logo">NEXT<span class="wf-logo-orange">PLAY</span></div>
                                    <div class="wf-preview-img-box">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="wf-line-placeholder wf-w-75"></div>
                                    <div class="wf-line-placeholder wf-w-50"></div>
                                    <div class="wf-btn-placeholder"></div>
                                </div>
                                <!-- Right property buttons -->
                                <div class="wf-editor-tools">
                                    <div class="wf-tool-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Logo</span>
                                    </div>
                                    <div class="wf-tool-btn">
                                        <span class="wf-tool-text-icon">Aa</span>
                                        <span>Text</span>
                                    </div>
                                    <div class="wf-tool-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        <span>Layout</span>
                                    </div>
                                    <div class="wf-tool-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5h4a2 2 0 012 2v4a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                        <span>Colors</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 3 -> 4 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 4: Choose what to show or hide -->
                        <div class="wf-card">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">4</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Choose what to show or hide</h3>
                                    <p class="wf-card-desc">Control which content blocks and details appear in the email.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-toggles">
                                <div class="wf-toggle-row">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span class="wf-toggle-label">Order details</span>
                                    </div>
                                    <span class="wf-switch wf-switch-on"><span class="wf-switch-knob"></span></span>
                                </div>
                                <div class="wf-toggle-row">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span class="wf-toggle-label">Tracking information</span>
                                    </div>
                                    <span class="wf-switch wf-switch-on"><span class="wf-switch-knob"></span></span>
                                </div>
                                <div class="wf-toggle-row">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <span class="wf-toggle-label wf-toggle-muted">Promotional banner</span>
                                    </div>
                                    <span class="wf-switch wf-switch-off"><span class="wf-switch-knob"></span></span>
                                </div>
                                <div class="wf-toggle-row">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v3a2 2 0 01-2 2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                                        <span class="wf-toggle-label wf-toggle-muted">Referral section</span>
                                    </div>
                                    <span class="wf-switch wf-switch-off"><span class="wf-switch-knob"></span></span>
                                </div>
                                <div class="wf-toggle-row">
                                    <div class="wf-flex-center gap-2 truncate">
                                        <svg class="wf-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="wf-toggle-label">Help &amp; support</span>
                                    </div>
                                    <span class="wf-switch wf-switch-on"><span class="wf-switch-knob"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 4 -> 5 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 5: Preview with sample data -->
                        <div class="wf-card">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">5</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Preview with sample data</h3>
                                    <p class="wf-card-desc">See how the email will look for your customers using sample data.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-preview-doc">
                                <div class="wf-preview-inner-card">
                                    <div class="wf-mini-logo">NEXT<span class="wf-logo-orange">PLAY</span></div>
                                    <div class="wf-line-placeholder wf-w-75 wf-mx-auto"></div>

                                    <!-- Delivery vehicle illustration with speed lines -->
                                    <div class="wf-delivery-art">
                                        <div class="wf-motion-lines">
                                            <span class="wf-mline wf-mline-orange wf-w-16"></span>
                                            <span class="wf-mline wf-mline-orange wf-w-10"></span>
                                        </div>
                                        <div class="wf-truck-icon-wrap">
                                            <svg viewBox="0 0 32 32" fill="none">
                                                <rect x="2" y="10" width="18" height="12" rx="2" fill="#0B2A4A"/>
                                                <path d="M 20 13 L 26 13 L 29 17 L 29 22 L 20 22 Z" fill="#0B2A4A"/>
                                                <!-- Window -->
                                                <path d="M 21 14.5 L 25 14.5 L 27.5 17.5 L 21 17.5 Z" fill="#94A3B8"/>
                                                <!-- Parcel inside / logo -->
                                                <rect x="7" y="13" width="8" height="6" rx="1.5" fill="#CF5D38"/>
                                                <!-- Wheels -->
                                                <circle cx="8" cy="23" r="3" fill="#1E293B" stroke="#FFFFFF" stroke-width="1.5"/>
                                                <circle cx="24" cy="23" r="3" fill="#1E293B" stroke="#FFFFFF" stroke-width="1.5"/>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="wf-line-placeholder wf-w-75 wf-mx-auto"></div>
                                    <div class="wf-line-placeholder wf-w-50 wf-mx-auto"></div>
                                    <div class="wf-btn-placeholder wf-mx-auto"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Flow Connector Line from Step 5 to Step 6 -->
                    <div class="wf-connector-track">
                        <svg class="wf-connector-svg" viewBox="0 0 1000 48" fill="none" preserveAspectRatio="none">
                            <!-- Loop down from card 5, run left horizontally across, and enter top-left of card 6 -->
                            <path d="M 910 0 L 910 20 Q 910 28 900 28 L 50 28 Q 40 28 40 36 L 40 44" stroke="#CBD5E1" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                            <polygon points="35,42 45,42 40,48" fill="#94A3B8"/>
                        </svg>
                    </div>

                    <!-- ROW 2: Steps 6 through 9 -->
                    <div class="wf-row wf-row-2">

                        <!-- Step 6: Save draft or Publish changes -->
                        <div class="wf-card wf-card-r2">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">6</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Save draft or Publish changes</h3>
                                    <p class="wf-card-desc">Save a draft to keep working, or publish to make changes live for the next email send.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-actions">
                                <div class="wf-action-btn wf-btn-draft">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Save Draft</span>
                                </div>
                                <div class="wf-action-btn wf-btn-publish">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <span>Publish Changes</span>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 6 -> 7 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 7: Next matching event uses latest published version -->
                        <div class="wf-card wf-card-r2">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">7</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Next matching event uses latest published version</h3>
                                    <p class="wf-card-desc">When the next matching event occurs (e.g., Delivery Estimate Updated), the system loads the latest published version.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-centered">
                                <div class="wf-db-container">
                                    <!-- 3D Database Cylinders SVG matching abc.png -->
                                    <svg class="wf-db-svg" viewBox="0 0 120 100" fill="none">
                                        <!-- Top disk -->
                                        <path d="M 15 26 V 42 C 15 54 105 54 105 42 V 26" fill="#CBD5E1"/>
                                        <ellipse cx="60" cy="26" rx="45" ry="14" fill="#E2E8F0" stroke="#CBD5E1" stroke-width="2"/>
                                        <ellipse cx="60" cy="42" rx="45" ry="14" fill="#CBD5E1" stroke="#94A3B8" stroke-width="2"/>
                                        <!-- Middle disk -->
                                        <path d="M 15 42 V 58 C 15 70 105 70 105 58 V 42" fill="#CBD5E1"/>
                                        <ellipse cx="60" cy="58" rx="45" ry="14" fill="#CBD5E1" stroke="#94A3B8" stroke-width="2"/>
                                        <!-- Bottom disk -->
                                        <path d="M 15 58 V 74 C 15 86 105 86 105 74 V 58" fill="#CBD5E1"/>
                                        <ellipse cx="60" cy="74" rx="45" ry="14" fill="#CBD5E1" stroke="#94A3B8" stroke-width="2"/>
                                    </svg>
                                    <!-- Circular orange sync badge -->
                                    <div class="wf-badge-sync">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 7 -> 8 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 8: Email is sent to customer -->
                        <div class="wf-card wf-card-r2">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">8</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Email is sent to customer</h3>
                                    <p class="wf-card-desc">The system sends the email using the latest published template version.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-centered">
                                <div class="wf-envelope-container">
                                    <!-- Motion speed lines on left -->
                                    <div class="wf-mail-motion">
                                        <span class="wf-mline wf-mline-slate wf-w-16"></span>
                                        <span class="wf-mline wf-mline-slate wf-w-24"></span>
                                        <span class="wf-mline wf-mline-slate wf-w-12"></span>
                                    </div>
                                    <!-- Envelope vector matching abc.png -->
                                    <svg class="wf-envelope-svg" viewBox="0 0 110 74" fill="none">
                                        <rect width="110" height="74" rx="10" fill="#CBD5E1"/>
                                        <path d="M 6 10 L 55 48 L 104 10" stroke="#94A3B8" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M 6 64 L 42 36" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M 104 64 L 68 36" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                    <!-- Circular orange airplane badge -->
                                    <div class="wf-badge-plane">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow 8 -> 9 -->
                        <div class="wf-arrow-wrap">
                            <svg class="wf-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>

                        <!-- Step 9: Customer sees the updated design and content -->
                        <div class="wf-card wf-card-r2">
                            <div class="wf-card-header">
                                <span class="wf-num-badge">9</span>
                                <div class="wf-card-meta">
                                    <h3 class="wf-card-title">Customer sees the updated design and content</h3>
                                    <p class="wf-card-desc">Your customer receives the email with the new branding, content, and layout.</p>
                                </div>
                            </div>
                            <div class="wf-mockup-frame wf-mockup-centered">
                                <div class="wf-monitor-container">
                                    <!-- Radiance burst strokes around monitor -->
                                    <span class="wf-ray wf-ray-tl"></span>
                                    <span class="wf-ray wf-ray-t"></span>
                                    <span class="wf-ray wf-ray-tr"></span>
                                    <span class="wf-ray wf-ray-l"></span>
                                    <span class="wf-ray wf-ray-r"></span>

                                    <!-- Monitor screen -->
                                    <div class="wf-monitor-screen">
                                        <div class="wf-mini-logo">NEXT<span class="wf-logo-orange">PLAY</span></div>
                                        <div class="wf-truck-icon-wrap wf-truck-sm">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#0B2A4A" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 3v5a1 1 0 01-1 1h-1m-4 0h-1"/></svg>
                                        </div>
                                        <div class="wf-line-placeholder wf-w-75 wf-mx-auto"></div>
                                        <div class="wf-btn-placeholder wf-mx-auto"></div>
                                    </div>
                                    <!-- Monitor stand -->
                                    <div class="wf-monitor-neck"></div>
                                    <div class="wf-monitor-base"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Version Status Bottom Bar (Matching abc.png) -->
            <div class="wf-status-footer">
                <div class="wf-status-title-box">
                    <h4 class="wf-status-title">Version Status</h4>
                    <p class="wf-status-subtitle">Templates can be in a draft or published state.</p>
                </div>
                <div class="wf-status-pills">
                    <div class="wf-status-item">
                        <span class="wf-pill-badge wf-pill-draft">Draft</span>
                        <div class="wf-status-desc">
                            <strong>Draft:</strong> Changes are saved but not live. The current published version continues to be used for email sends.
                        </div>
                    </div>
                    <div class="wf-status-item">
                        <span class="wf-pill-badge wf-pill-published">Published</span>
                        <div class="wf-status-desc">
                            <strong>Published:</strong> Active version used for the next matching email send.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scoped, Bulletproof CSS Layout matching blueprint abc.png -->
    <style>
        .wf-canvas {
            background-color: #F3F7FA;
            border: 1px solid #E2E8F0;
            border-radius: 28px;
            padding: 32px 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .wf-header {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 28px;
        }
        @media (min-width: 1024px) {
            .wf-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .wf-title-block {
            max-width: 680px;
        }

        .wf-logo {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 0.05em;
            color: #0A2540;
            margin-bottom: 4px;
        }
        .wf-logo-orange {
            color: #CF5D38;
        }

        .wf-main-title {
            font-size: 28px;
            font-weight: 900;
            color: #0A2540;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin: 0;
        }
        @media (min-width: 640px) {
            .wf-main-title {
                font-size: 32px;
            }
        }

        .wf-main-subtitle {
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.5;
            color: #64748B;
        }

        .wf-notice-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background-color: #FFF5ED;
            border: 1px solid #FED7AA;
            border-radius: 18px;
            padding: 14px 18px;
            max-width: 420px;
            box-shadow: 0 1px 2px rgba(207, 93, 56, 0.05);
        }
        .wf-notice-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background-color: #CF5D38;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 900;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .wf-notice-heading {
            font-size: 12px;
            font-weight: 800;
            color: #9A3412;
            line-height: 1.3;
        }
        .wf-notice-text {
            font-size: 11px;
            color: #C2410C;
            margin-top: 2px;
            line-height: 1.4;
        }

        /* Diagram Layout */
        .wf-diagram-scroll {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 12px;
        }
        .wf-diagram-inner {
            min-width: 1180px;
        }

        .wf-row {
            display: flex;
            align-items: stretch;
            width: 100%;
        }
        .wf-row-1 .wf-card {
            flex: 1 1 0;
            min-width: 0;
        }
        .wf-row-2 .wf-card-r2 {
            flex: 1 1 0;
            min-width: 0;
        }

        .wf-arrow-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            flex-shrink: 0;
        }
        .wf-arrow {
            width: 18px;
            height: 18px;
            color: #94A3B8;
        }

        /* Card Styling */
        .wf-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 24px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .wf-card:hover {
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07);
        }

        .wf-card-header {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            min-height: 60px;
        }
        .wf-num-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #CF5D38;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 900;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(207, 93, 56, 0.25);
        }
        .wf-card-meta {
            min-width: 0;
        }
        .wf-card-title {
            font-size: 13px;
            font-weight: 800;
            color: #0A2540;
            line-height: 1.25;
            margin: 0;
        }
        .wf-card-desc {
            font-size: 10.5px;
            color: #64748B;
            line-height: 1.35;
            margin-top: 3px;
            margin-bottom: 0;
        }

        /* Mockup Containers */
        .wf-mockup-frame {
            border-radius: 18px;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 12px;
            height: 195px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Step 1: Admin Sidebar Mockup */
        .wf-mockup-sidebar {
            background-color: #0F1D2F;
            border-color: #1E293B;
            padding: 12px;
            color: #FFFFFF;
            justify-content: flex-start;
            gap: 5.5px;
        }
        .wf-sb-logo {
            font-size: 11px;
            font-weight: 900;
            color: #FFFFFF;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .wf-sb-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 9.5px;
            color: #94A3B8;
        }
        .wf-sb-item svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }
        .wf-sb-group {
            border-top: 1px solid #1E293B;
            padding-top: 5px;
            margin-top: 2px;
        }
        .wf-sb-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9.5px;
            font-weight: 800;
            color: #CBD5E1;
        }
        .wf-sb-group-title svg {
            width: 13px;
            height: 13px;
        }
        .wf-sb-subitem {
            margin-top: 4px;
            padding: 3.5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .wf-sb-subitem-active {
            background-color: rgba(207, 93, 56, 0.22);
            color: #FF855F;
            border: 1px solid rgba(207, 93, 56, 0.35);
        }
        .wf-dot-orange {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background-color: #CF5D38;
        }

        /* Step 2: Template Selection List */
        .wf-mockup-list {
            padding: 10px;
            gap: 5.5px;
            justify-content: center;
        }
        .wf-template-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 9px;
            border-radius: 10px;
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
        }
        .wf-template-item svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
        .wf-template-active {
            background-color: #FFF5ED;
            border-color: #FED7AA;
            color: #9A3412;
            font-weight: 800;
        }
        .wf-chevron {
            color: #94A3B8;
            font-weight: 800;
            font-size: 11px;
            margin-left: 4px;
        }
        .wf-template-active .wf-chevron {
            color: #CF5D38;
        }

        /* Step 3: Editor Split View */
        .wf-mockup-editor {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 9px;
            align-items: center;
            padding: 10px;
        }
        .wf-editor-preview {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 7px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .wf-mini-logo {
            font-size: 8.5px;
            font-weight: 900;
            color: #0A2540;
            text-align: center;
        }
        .wf-preview-img-box {
            height: 32px;
            background-color: #F1F5F9;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
            display: grid;
            place-items: center;
        }
        .wf-preview-img-box svg {
            width: 15px;
            height: 15px;
        }
        .wf-line-placeholder {
            height: 4px;
            border-radius: 3px;
            background-color: #E2E8F0;
        }
        .wf-w-75 { width: 75%; }
        .wf-w-50 { width: 50%; }
        .wf-mx-auto { margin-left: auto; margin-right: auto; }
        .wf-btn-placeholder {
            height: 10px;
            border-radius: 4px;
            background-color: #0B2A4A;
            width: 100%;
            margin-top: 2px;
        }
        .wf-editor-tools {
            display: flex;
            flex-direction: column;
            gap: 4.5px;
        }
        .wf-tool-btn {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 5px 7px;
            font-size: 9px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        .wf-tool-btn svg {
            width: 12px;
            height: 12px;
        }
        .wf-tool-text-icon {
            font-size: 9px;
            font-weight: 900;
            color: #64748B;
        }

        /* Step 4: Toggles Frame */
        .wf-mockup-toggles {
            padding: 10px;
            gap: 4.5px;
            justify-content: center;
        }
        .wf-toggle-row {
            background-color: #FFFFFF;
            border: 1px solid #F1F5F9;
            border-radius: 8px;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9px;
            font-weight: 700;
            color: #1E293B;
        }
        .wf-toggle-icon {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }
        .wf-toggle-muted {
            color: #94A3B8;
            font-weight: 500;
        }
        .wf-switch {
            width: 24px;
            height: 13px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            padding: 1px;
            flex-shrink: 0;
            transition: background-color 0.2s;
        }
        .wf-switch-knob {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background-color: #FFFFFF;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
        }
        .wf-switch-on {
            background-color: #CF5D38;
            justify-content: flex-end;
        }
        .wf-switch-off {
            background-color: #CBD5E1;
            justify-content: flex-start;
        }

        /* Step 5: Document Preview with Truck */
        .wf-mockup-preview-doc {
            padding: 10px;
        }
        .wf-preview-inner-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 9px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            box-sizing: border-box;
        }
        .wf-delivery-art {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 4px 0;
        }
        .wf-motion-lines {
            display: flex;
            flex-direction: column;
            gap: 2.5px;
            align-items: flex-end;
        }
        .wf-mline {
            height: 2px;
            border-radius: 2px;
            display: block;
        }
        .wf-mline-orange { background-color: #CF5D38; }
        .wf-mline-slate { background-color: #CBD5E1; }
        .wf-w-10 { width: 10px; }
        .wf-w-12 { width: 12px; }
        .wf-w-16 { width: 16px; }
        .wf-w-24 { width: 24px; }

        .wf-truck-icon-wrap {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
        }
        .wf-truck-icon-wrap svg {
            width: 30px;
            height: 30px;
        }

        /* Looping Connector Path */
        .wf-connector-track {
            width: 100%;
            height: 48px;
            margin: 4px 0;
            position: relative;
        }
        .wf-connector-svg {
            width: 100%;
            height: 48px;
            display: block;
        }

        /* Row 2 Custom Elements */
        .wf-mockup-actions {
            padding: 16px 14px;
            gap: 10px;
            justify-content: center;
        }
        .wf-action-btn {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        .wf-action-btn:hover {
            transform: translateY(-1px);
        }
        .wf-action-btn svg {
            width: 15px;
            height: 15px;
        }
        .wf-btn-draft {
            background-color: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #334155;
        }
        .wf-btn-publish {
            background-color: #CF5D38;
            border: 1px solid #CF5D38;
            color: #FFFFFF;
            box-shadow: 0 2px 6px rgba(207, 93, 56, 0.25);
        }

        .wf-mockup-centered {
            align-items: center;
            justify-content: center;
        }

        /* Step 7 Database */
        .wf-db-container {
            position: relative;
            width: 120px;
            height: 100px;
            display: grid;
            place-items: center;
        }
        .wf-db-svg {
            width: 108px;
            height: 90px;
        }
        .wf-badge-sync {
            position: absolute;
            bottom: 2px;
            right: 4px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #CF5D38;
            display: grid;
            place-items: center;
            box-shadow: 0 2px 6px rgba(207, 93, 56, 0.4);
        }
        .wf-badge-sync svg {
            width: 16px;
            height: 16px;
        }

        /* Step 8 Envelope */
        .wf-envelope-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wf-mail-motion {
            position: absolute;
            left: -28px;
            display: flex;
            flex-direction: column;
            gap: 3.5px;
        }
        .wf-envelope-svg {
            width: 110px;
            height: 74px;
        }
        .wf-badge-plane {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: #CF5D38;
            display: grid;
            place-items: center;
            box-shadow: 0 2px 6px rgba(207, 93, 56, 0.4);
        }
        .wf-badge-plane svg {
            width: 16px;
            height: 16px;
            transform: rotate(-15deg) translate(1px, 0);
        }

        /* Step 9 Monitor */
        .wf-monitor-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 10px;
        }
        .wf-ray {
            position: absolute;
            background-color: #CF5D38;
            border-radius: 2px;
        }
        .wf-ray-t  { width: 3px; height: 12px; top: -8px; left: calc(50% - 1.5px); }
        .wf-ray-tl { width: 3px; height: 10px; top: -3px; left: 18px; transform: rotate(-35deg); }
        .wf-ray-tr { width: 3px; height: 10px; top: -3px; right: 18px; transform: rotate(35deg); }
        .wf-ray-l  { width: 10px; height: 3px; top: 26px; left: -16px; }
        .wf-ray-r  { width: 10px; height: 3px; top: 26px; right: -16px; }

        .wf-monitor-screen {
            width: 124px;
            background-color: #FFFFFF;
            border: 2px solid #CBD5E1;
            border-radius: 12px;
            padding: 7px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .wf-truck-sm svg {
            width: 22px;
            height: 22px;
        }
        .wf-monitor-neck {
            width: 16px;
            height: 7px;
            background-color: #CBD5E1;
        }
        .wf-monitor-base {
            width: 52px;
            height: 4px;
            border-radius: 3px;
            background-color: #94A3B8;
        }

        /* Version Status Footer */
        .wf-status-footer {
            margin-top: 28px;
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        @media (min-width: 768px) {
            .wf-status-footer {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .wf-status-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #0A2540;
            margin: 0;
        }
        .wf-status-subtitle {
            font-size: 11px;
            color: #64748B;
            margin-top: 2px;
            margin-bottom: 0;
        }
        .wf-status-pills {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        @media (min-width: 640px) {
            .wf-status-pills {
                flex-direction: row;
                align-items: center;
                gap: 24px;
            }
        }
        .wf-status-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .wf-pill-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            flex-shrink: 0;
        }
        .wf-pill-draft {
            background-color: #FEF3C7;
            color: #92400E;
            border: 1px solid #FDE68A;
        }
        .wf-pill-published {
            background-color: #DCFCE7;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .wf-status-desc {
            font-size: 11px;
            color: #475569;
            line-height: 1.4;
        }
        .wf-status-desc strong {
            color: #0F172A;
            font-weight: 800;
        }

        .wf-flex-center {
            display: flex;
            align-items: center;
        }
    </style>
</x-layouts.admin>
