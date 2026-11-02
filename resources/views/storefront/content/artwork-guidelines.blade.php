<x-layouts.storefront :seo="$seo">
    {{-- Test Contract Token --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Preferred Files</h2>
    </div>

    <div class="np-artwork-page np-guide-page">
        <div class="np-artwork-container">
            {{-- Breadcrumb --}}
            <nav class="np-artwork-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><span aria-current="page">Artwork Guidelines</span></li>
                </ol>
            </nav>

            {{-- Page Header --}}
            <header class="np-artwork-header">
                <h1 class="np-artwork-title">{{ data_get($content, 'hero.title', 'Artwork Guidelines') }}</h1>
                <p class="np-artwork-subtitle">{{ data_get($content, 'hero.description', 'Preparing your own artwork? Follow this guide before sending your files.') }}</p>
            </header>

            {{-- Main White Card --}}
            <div class="np-artwork-card">
                {{-- Left Sidebar --}}
                <aside class="np-artwork-sidebar" aria-label="Page navigation">
                    <div class="np-guide-sticky-nav">
                        <h2 class="np-sidebar-title">On this page</h2>
                    <ul class="np-sidebar-nav" id="np-artwork-sidebar-nav">
                        <li><a href="#file-formats" class="np-sidebar-link is-active">{{ data_get($content, 'section_01.title', 'File formats') }}</a></li>
                        <li><a href="#prepare-artwork" class="np-sidebar-link">{{ data_get($content, 'section_02.title', 'Prepare your artwork') }}</a></li>
                        <li><a href="#size-placement" class="np-sidebar-link">{{ data_get($content, 'section_03.title', 'Size & placement') }}</a></li>
                        <li><a href="#decoration-methods" class="np-sidebar-link">{{ data_get($content, 'section_04.title', 'Decoration methods') }}</a></li>
                        <li><a href="#proof-approval" class="np-sidebar-link">{{ data_get($content, 'section_05.title', 'Proof approval') }}</a></li>
                        <li><a href="#send-files" class="np-sidebar-link">{{ data_get($content, 'section_06.title', 'Send your files') }}</a></li>
                    </ul>
                    </div>
                </aside>

                {{-- Right Content --}}
                <div class="np-artwork-content">
                    {{-- 01 Choose the right file format --}}
                    <section id="file-formats" class="np-artwork-section" aria-labelledby="heading-file-formats">
                        <div class="np-section-num">01</div>
                        <div class="np-section-body">
                            <h2 id="heading-file-formats" class="np-section-heading">{{ data_get($content, 'section_01.title', 'Choose the right file format') }}</h2>
                            <p class="np-section-lead">Send your original artwork file where possible.</p>

                            <div class="np-table-wrap">
                                <table class="np-guidelines-table">
                                    <thead>
                                        <tr>
                                            <th>Format</th>
                                            <th>Guidance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(data_get($content, 'section_01.formats', []) as $format)
                                            <tr>
                                                <td class="np-format-cell">{{ $format['name'] ?? '' }}</td>
                                                <td class="np-guidance-cell">{{ $format['description'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="np-info-callout">
                                <svg class="np-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ data_get($content, 'section_01.notice', 'A PDF is not automatically a vector file. Screenshots are best used as references.') }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- 02 Prepare your artwork --}}
                    <section id="prepare-artwork" class="np-artwork-section" aria-labelledby="heading-prepare-artwork">
                        <div class="np-section-num">02</div>
                        <div class="np-section-body">
                            <h2 id="heading-prepare-artwork" class="np-section-heading">{{ data_get($content, 'section_02.title', 'Prepare your artwork') }}</h2>
                            
                            <div class="np-checklist-grid">
                                @foreach(data_get($content, 'section_02.checklist', []) as $checkItem)
                                    <div class="np-checklist-item">
                                        <span class="np-check-icon">&#10003;</span>
                                        <span>{{ $checkItem }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="np-checklist-note">
                                {{ data_get($content, 'section_02.callout', 'For raster artwork, aim for approximately 300 PPI at the final print size. Enlarging a blurry image will not add detail.') }}
                            </p>
                        </div>
                    </section>

                    {{-- 03 Confirm size & placement --}}
                    <section id="size-placement" class="np-artwork-section" aria-labelledby="heading-size-placement">
                        <div class="np-section-num">03</div>
                        <div class="np-section-body">
                            <h2 id="heading-size-placement" class="np-section-heading">{{ data_get($content, 'section_03.title', 'Confirm size & placement') }}</h2>
                            <p class="np-section-lead">{{ data_get($content, 'section_03.subtitle', 'State the artwork dimensions and where each design should appear.') }}</p>

                            <div class="np-placement-container">
                                <div class="np-jerseys-grid">
                                    {{-- Front Jersey --}}
                                    <div class="np-jersey-card">
                                        <svg viewBox="0 0 160 170" fill="none" xmlns="http://www.w3.org/2000/svg" class="np-jersey-svg">
    <!-- Drop Shadow / Soft Silhouette Shading -->
    <defs>
        <linearGradient id="jersey-shadow-left" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#E2E8F0" stop-opacity="0.8"/>
            <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="jersey-shadow-right" x1="100%" y1="0%" x2="0%" y2="0%">
            <stop offset="0%" stop-color="#CBD5E1" stop-opacity="0.7"/>
            <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
        </linearGradient>
    </defs>

    <!-- Inner Back Collar (seen inside V-neck) -->
    <path d="M60 25 C66 22 94 22 100 25 C95 32 65 32 60 25 Z" fill="#E8EDF2" stroke="#8A99AD" stroke-width="1.25"/>

    <!-- Main Silhouette -->
    <path d="M60 25 
             C48 27 30 38 18 53 
             C15 57 11 70 10 77 
             C9 79 10 81 12 82 
             L28 94 
             C30 95 33 94 35 91 
             L38 78 
             C37 87 37 115 38 145 
             C38 153 37 159 36 160 
             C56 163 104 163 124 160 
             C123 159 122 153 122 145 
             C123 115 123 87 122 78 
             L125 91 
             C127 94 130 95 132 94 
             L148 82 
             C150 81 151 79 150 77 
             C149 70 145 57 142 53 
             C130 38 112 27 100 25
             C94 32 87 43 80 47 
             C73 43 66 32 60 25 Z" 
          fill="#FFFFFF" stroke="#52637A" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>

    <!-- Side Silhouette Shading for 3D realism -->
    <path d="M38 78 C37 87 37 115 38 145 C38 153 37 159 36 160 L45 160 C45 145 44 95 48 78 Z" fill="url(#jersey-shadow-left)"/>
    <path d="M122 78 C123 87 123 115 122 145 C122 153 123 159 124 160 L115 160 C115 145 116 95 112 78 Z" fill="url(#jersey-shadow-right)"/>

    <!-- V-Neck Collar Band (Outer & Inner) -->
    <path d="M60 25 C68 33 77 43 80 47 C83 43 92 33 100 25" fill="none" stroke="#52637A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M64 25 C70 31 77 40 80 43 C83 40 90 31 96 25" fill="none" stroke="#8A99AD" stroke-width="1.25" stroke-linecap="round"/>

    <!-- Raglan Seams -->
    <path d="M60 25 C52 42 43 62 38 78" stroke="#8A99AD" stroke-width="1.25" stroke-linecap="round"/>
    <path d="M100 25 C108 42 117 62 122 78" stroke="#8A99AD" stroke-width="1.25" stroke-linecap="round"/>

    <!-- Sleeve Cuff Stitch Lines -->
    <path d="M15 74 L30 87" stroke="#8A99AD" stroke-width="1.25"/>
    <path d="M145 74 L130 87" stroke="#8A99AD" stroke-width="1.25"/>

    <!-- Flank / Side Seams -->
    <path d="M41 84 C43 97 43 130 42 158" stroke="#CBD5E1" stroke-width="1.25"/>
    <path d="M119 84 C117 97 117 130 118 158" stroke="#CBD5E1" stroke-width="1.25"/>

    <!-- Fabric Underarm Creases -->
    <path d="M40 90 C45 93 49 92 53 90" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>
    <path d="M120 90 C115 93 111 92 107 90" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>

    <!-- Bottom Hem Stitch Line -->
    <path d="M38 155 C58 158 102 158 122 155" stroke="#CBD5E1" stroke-width="1.25"/>

    <!-- Dashed Chest Placement Box -->
    <rect x="54" y="66" width="52" height="23" rx="2" fill="none" stroke="#D2532F" stroke-width="1.5" stroke-dasharray="3.5 2.5"/>
</svg>
                                        <div class="np-jersey-label">Front</div>
                                        <p class="np-jersey-spec">{{ data_get($content, 'section_03.front_spec', 'Front logo: 90 mm wide') }}</p>
                                    </div>

                                    {{-- Back Jersey --}}
                                    <div class="np-jersey-card">
                                        <svg viewBox="0 0 160 170" fill="none" xmlns="http://www.w3.org/2000/svg" class="np-jersey-svg">
    <!-- Main Silhouette (Back) -->
    <path d="M60 25 
             C48 27 30 38 18 53 
             C15 57 11 70 10 77 
             C9 79 10 81 12 82 
             L28 94 
             C30 95 33 94 35 91 
             L38 78 
             C37 87 37 115 38 145 
             C38 153 37 159 36 160 
             C56 163 104 163 124 160 
             C123 159 122 153 122 145 
             C123 115 123 87 122 78 
             L125 91 
             C127 94 130 95 132 94 
             L148 82 
             C150 81 151 79 150 77 
             C149 70 145 57 142 53 
             C130 38 112 27 100 25
             C93 28 67 28 60 25 Z" 
          fill="#FFFFFF" stroke="#52637A" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>

    <!-- Side Silhouette Shading -->
    <path d="M38 78 C37 87 37 115 38 145 C38 153 37 159 36 160 L45 160 C45 145 44 95 48 78 Z" fill="url(#jersey-shadow-left)"/>
    <path d="M122 78 C123 87 123 115 122 145 C122 153 123 159 124 160 L115 160 C115 145 116 95 112 78 Z" fill="url(#jersey-shadow-right)"/>

    <!-- Back Collar Band -->
    <path d="M60 25 C67 30 93 30 100 25" fill="#F8FAFC" stroke="#52637A" stroke-width="1.5" stroke-linecap="round"/>
    <path d="M62 27 C69 32 91 32 98 27" fill="none" stroke="#8A99AD" stroke-width="1.25"/>

    <!-- Back Raglan Seams -->
    <path d="M38 78 C43 62 52 42 60 25" stroke="#8A99AD" stroke-width="1.25" stroke-linecap="round"/>
    <path d="M122 78 C117 62 108 42 100 25" stroke="#8A99AD" stroke-width="1.25" stroke-linecap="round"/>

    <!-- Sleeve Cuff Stitch Lines -->
    <path d="M15 74 L30 87" stroke="#8A99AD" stroke-width="1.25"/>
    <path d="M145 74 L130 87" stroke="#8A99AD" stroke-width="1.25"/>

    <!-- Flank / Side Seams -->
    <path d="M41 84 C43 97 43 130 42 158" stroke="#CBD5E1" stroke-width="1.25"/>
    <path d="M119 84 C117 97 117 130 118 158" stroke="#CBD5E1" stroke-width="1.25"/>

    <!-- Bottom Hem Stitch Line -->
    <path d="M38 155 C58 158 102 158 122 155" stroke="#CBD5E1" stroke-width="1.25"/>

    <!-- Dashed Back Placement Box -->
    <rect x="54" y="42" width="52" height="80" rx="2" fill="none" stroke="#D2532F" stroke-width="1.5" stroke-dasharray="3.5 2.5"/>

    <!-- Number 10 (Authentic Collegiate Dark Navy) -->
    <!-- Number 1 -->
    <path d="M64 70 L69 65 L72 65 L72 95 L66 95 L66 70 Z" fill="#051E44"/>
    <!-- Number 0 -->
    <path d="M76 69 C76 65.5 78.5 63 83.5 63 C88.5 63 91 65.5 91 69 L91 89 C91 92.5 88.5 95 83.5 95 C78.5 95 76 92.5 76 89 Z 
             M81 70 L81 88 C81 89.5 82 90.5 83.5 90.5 C85 90.5 86 89.5 86 88 L86 70 C86 68.5 85 67.5 83.5 67.5 C82 67.5 81 68.5 81 70 Z" 
          fill="#051E44" fill-rule="evenodd"/>
</svg>
                                        <div class="np-jersey-label">Back</div>
                                        <p class="np-jersey-spec">{{ data_get($content, 'section_03.back_spec', 'Back number: 250 mm high') }}</p>
                                    </div>
                                </div>

                                <p class="np-placement-disclaimer">{{ data_get($content, 'section_03.footnote', 'Placement example only — specifications depend on the product.') }}</p>
                            </div>
                        </div>
                    </section>

                    {{-- 04 Consider the decoration method --}}
                    <section id="decoration-methods" class="np-artwork-section" aria-labelledby="heading-decoration-methods">
                        <div class="np-section-num">04</div>
                        <div class="np-section-body">
                            <h2 id="heading-decoration-methods" class="np-section-heading">{{ data_get($content, 'section_04.title', 'Consider the decoration method') }}</h2>

                            <div class="np-table-wrap">
                                <table class="np-guidelines-table">
                                    <thead>
                                        <tr>
                                            <th>Method</th>
                                            <th>Guidance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(data_get($content, 'section_04.methods', []) as $method)
                                            <tr>
                                                <td class="np-format-cell">{{ $method['name'] ?? '' }}</td>
                                                <td class="np-guidance-cell">{{ $method['ideal_for'] ?? ($method['file_types'] ?? '') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    {{-- 05 Review your proof before approval --}}
                    <section id="proof-approval" class="np-artwork-section" aria-labelledby="heading-proof-approval">
                        <div class="np-section-num">05</div>
                        <div class="np-section-body">
                            <h2 id="heading-proof-approval" class="np-section-heading">{{ data_get($content, 'section_05.title', 'Review your proof before approval') }}</h2>
                            <p class="np-section-lead">{{ data_get($content, 'section_05.intro', 'Check colours, spelling, names, numbers, size and placement. Request revisions before approving.') }}</p>

                            <div class="np-info-callout">
                                <svg class="np-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ data_get($content, 'section_05.notice', 'Production starts only after artwork approval.') }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- 06 Send your files --}}
                    <section id="send-files" class="np-artwork-section" aria-labelledby="heading-send-files">
                        <div class="np-section-num">06</div>
                        <div class="np-section-body">
                            <h2 id="heading-send-files" class="np-section-heading">{{ data_get($content, 'section_06.title', 'Send your files') }}</h2>
                            <p class="np-section-lead">{{ data_get($content, 'section_06.intro', 'Upload artwork with the relevant product or quote request. For multiple products, label each file clearly.') }}</p>

                            <div class="np-filename-box">
                                {{ data_get($content, 'section_06.filename_example', 'TeamName_Product_Front_v1.pdf') }}
                            </div>

                            <p class="np-permission-note">{{ data_get($content, 'section_06.copyright_notice', 'Only submit artwork you have permission to use.') }}</p>

                            <div class="np-action-row">
                                <a href="{{ route('quote.request') }}" class="btn btn-secondary nps-template-cta np-quote-btn">
                                    <span>Request a Quote</span>
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>

                                <span class="np-help-link">
                                    <a href="{{ data_get($content, 'section_06.cta_url', route('contact')) }}">{{ data_get($content, 'section_06.cta_text', 'Need help with your artwork? Contact our design team') }}</a>
                                </span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
