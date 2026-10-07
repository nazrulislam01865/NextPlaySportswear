@props(['settings'])

@php
    $submitted = old('settings', []);
    $settings = array_replace(
        \App\Support\ProductStorefrontUi::defaults(),
        is_array($settings ?? null) ? $settings : [],
        is_array($submitted) ? $submitted : []
    );
    $checked = fn (string $key): bool => filter_var($settings[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="np-pdui-editor">
    <section class="np-pdui-section">
        <header class="np-pdui-section__header">
            <div>
                <p class="np-pdui-eyebrow">Customer actions</p>
                <h2>Product action buttons</h2>
                <p>Control the reusable action labels and icons shown near the product title.</p>
            </div>
        </header>

        <div class="np-pdui-grid np-pdui-grid--3">
            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading">
                    <div>
                        <strong>Save button</strong>
                        <small>Shown on every product unless disabled here.</small>
                    </div>
                    <label class="np-pdui-toggle-label">
                        <input type="hidden" name="settings[save_enabled]" value="0">
                        <input type="checkbox" name="settings[save_enabled]" value="1" @checked($checked('save_enabled'))>
                        <span>Show</span>
                    </label>
                </div>
                <label class="admin-label">Button text
                    <input class="admin-input" name="settings[save_label]" value="{{ $settings['save_label'] }}" maxlength="80">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="save_icon" label="Save icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading">
                    <div>
                        <strong>Share button</strong>
                        <small>Shown on every product unless disabled here.</small>
                    </div>
                    <label class="np-pdui-toggle-label">
                        <input type="hidden" name="settings[share_enabled]" value="0">
                        <input type="checkbox" name="settings[share_enabled]" value="1" @checked($checked('share_enabled'))>
                        <span>Show</span>
                    </label>
                </div>
                <label class="admin-label">Button text
                    <input class="admin-input" name="settings[share_label]" value="{{ $settings['share_label'] }}" maxlength="80">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="share_icon" label="Share icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading">
                    <div>
                        <strong>Request a Sample</strong>
                        <small>Its visibility still follows each product's Sample available setting.</small>
                    </div>
                </div>
                <label class="admin-label">Button text
                    <input class="admin-input" name="settings[sample_label]" value="{{ $settings['sample_label'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="sample_icon" label="Sample icon" />
            </article>
        </div>
    </section>

    <section class="np-pdui-section">
        <header class="np-pdui-section__header">
            <div>
                <p class="np-pdui-eyebrow">Size &amp; quantity</p>
                <h2>Size information and helper message</h2>
                <p>The labels and icons are global. Product-specific size values and minimum quantities remain controlled on each product.</p>
            </div>
        </header>

        <div class="np-pdui-grid np-pdui-grid--3">
            <article class="np-pdui-control-card">
                <label class="admin-label">Sizes label
                    <input class="admin-input" name="settings[sizes_label]" value="{{ $settings['sizes_label'] }}" maxlength="100">
                </label>
                <label class="admin-label">Sizes summary label
                    <input class="admin-input" name="settings[sizes_available_label]" value="{{ $settings['sizes_available_label'] }}" maxlength="100">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="sizes_icon" label="Sizes icon" />
            </article>

            <article class="np-pdui-control-card">
                <label class="admin-label">Minimum order label
                    <input class="admin-input" name="settings[minimum_order_label]" value="{{ $settings['minimum_order_label'] }}" maxlength="120">
                </label>
                <div class="np-pdui-spacer" aria-hidden="true"></div>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="minimum_order_icon" label="Minimum order icon" />
            </article>

            <article class="np-pdui-control-card">
                <label class="admin-label">Size guide button text
                    <input class="admin-input" name="settings[size_guide_label]" value="{{ $settings['size_guide_label'] }}" maxlength="120">
                </label>
                <div class="np-pdui-spacer" aria-hidden="true"></div>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="size_guide_icon" label="Size guide icon" />
            </article>
        </div>

        <div class="np-pdui-helper-row">
            <label class="admin-label">Sizes &amp; quantities helper text
                <textarea class="admin-textarea np-pdui-helper-text" name="settings[multiple_sizes_note]" maxlength="500">{{ $settings['multiple_sizes_note'] }}</textarea>
            </label>
            <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="multiple_sizes_note_icon" label="Helper text icon" />
        </div>
    </section>

    <section class="np-pdui-section">
        <header class="np-pdui-section__header">
            <div>
                <p class="np-pdui-eyebrow">Artwork step</p>
                <h2>Artwork labels, instructions and icons</h2>
                <p>These values are reused on every product that has the artwork step enabled.</p>
            </div>
        </header>

        <div class="np-pdui-grid np-pdui-grid--2 np-pdui-grid--heading-fields">
            <label class="admin-label">Artwork step title
                <input class="admin-input" name="settings[artwork_step_title]" value="{{ $settings['artwork_step_title'] }}" maxlength="120">
            </label>
            <label class="admin-label">Artwork step description
                <input class="admin-input" name="settings[artwork_step_description]" value="{{ $settings['artwork_step_description'] }}" maxlength="300">
            </label>
        </div>

        <div class="np-pdui-notice">
            Accepted file extensions, maximum file size, maximum files, and whether artwork is required remain product-specific in each product's <strong>Custom Artwork Upload</strong> section.
        </div>

        <div class="np-pdui-grid np-pdui-grid--3">
            @foreach([
                ['Upload tab', 'artwork_upload_tab_title', 'artwork_upload_tab_description', 'artwork_upload_tab_icon'],
                ['Existing design tab', 'artwork_existing_tab_title', 'artwork_existing_tab_description', 'artwork_existing_tab_icon'],
                ['Artwork help tab', 'artwork_help_tab_title', 'artwork_help_tab_description', 'artwork_help_tab_icon'],
            ] as [$heading, $titleKey, $descriptionKey, $iconKey])
                <article class="np-pdui-control-card">
                    <div class="np-pdui-control-card__heading"><div><strong>{{ $heading }}</strong></div></div>
                    <label class="admin-label">Title
                        <input class="admin-input" name="settings[{{ $titleKey }}]" value="{{ $settings[$titleKey] }}" maxlength="120">
                    </label>
                    <label class="admin-label">Description
                        <textarea class="admin-textarea np-pdui-description" name="settings[{{ $descriptionKey }}]" maxlength="300">{{ $settings[$descriptionKey] }}</textarea>
                    </label>
                    <x-admin.product-storefront-ui-icon-field :settings="$settings" :setting-key="$iconKey" :label="$heading.' icon'" />
                </article>
            @endforeach
        </div>

        <div class="np-pdui-grid np-pdui-grid--2">
            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Upload box text</strong><small>Text displayed inside and beside the artwork drop zone.</small></div></div>
                <label class="admin-label">Drop-zone heading
                    <input class="admin-input" name="settings[artwork_drop_title]" value="{{ $settings['artwork_drop_title'] }}" maxlength="160">
                </label>
                <label class="admin-label">Browse text
                    <input class="admin-input" name="settings[artwork_browse_text]" value="{{ $settings['artwork_browse_text'] }}" maxlength="120">
                </label>
                <div class="np-pdui-grid np-pdui-grid--2 np-pdui-grid--nested">
                    <label class="admin-label">Formats label
                        <input class="admin-input" name="settings[artwork_formats_label]" value="{{ $settings['artwork_formats_label'] }}" maxlength="120">
                    </label>
                    <label class="admin-label">Maximum size label
                        <input class="admin-input" name="settings[artwork_max_size_label]" value="{{ $settings['artwork_max_size_label'] }}" maxlength="120">
                    </label>
                </div>
                <label class="admin-label">Multiple-files text
                    <input class="admin-input" name="settings[artwork_multiple_files_text]" value="{{ $settings['artwork_multiple_files_text'] }}" maxlength="200">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="artwork_drop_icon" label="Upload box icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>What happens next</strong><small>Each non-empty line is rendered as one storefront bullet.</small></div></div>
                <label class="admin-label">Upload mode heading
                    <input class="admin-input" name="settings[artwork_next_title]" value="{{ $settings['artwork_next_title'] }}" maxlength="120">
                </label>
                <label class="admin-label">Upload mode lines
                    <textarea class="admin-textarea np-pdui-lines" name="settings[artwork_next_lines]" maxlength="1200">{{ $settings['artwork_next_lines'] }}</textarea>
                </label>
                <label class="admin-label">Help mode heading
                    <input class="admin-input" name="settings[artwork_help_next_title]" value="{{ $settings['artwork_help_next_title'] }}" maxlength="120">
                </label>
                <label class="admin-label">Help mode lines
                    <textarea class="admin-textarea np-pdui-lines" name="settings[artwork_help_next_lines]" maxlength="1200">{{ $settings['artwork_help_next_lines'] }}</textarea>
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="artwork_info_icon" label="Information icon" />
            </article>
        </div>
    </section>

    <section class="np-pdui-section">
        <header class="np-pdui-section__header">
            <div>
                <p class="np-pdui-eyebrow">Production &amp; shipping</p>
                <h2>Shipping, delivery estimate and customer notes</h2>
                <p>Control the shared labels, helper copy and fallback icons used in the Production &amp; Shipping step.</p>
            </div>
        </header>

        <div class="np-pdui-grid np-pdui-grid--2 np-pdui-grid--heading-fields">
            <label class="admin-label">Step title
                <input class="admin-input" name="settings[production_step_title]" value="{{ $settings['production_step_title'] }}" maxlength="120">
            </label>
            <label class="admin-label">Step description
                <input class="admin-input" name="settings[production_step_description]" value="{{ $settings['production_step_description'] }}" maxlength="300">
            </label>
        </div>

        <div class="np-pdui-notice">
            Shipping method names, descriptions, transit days and method-specific images remain reusable Master Data. Edit those under <strong>Master Data → Shipping Methods</strong>. The controls below manage the shared storefront labels and fallback icons.
        </div>

        <div class="np-pdui-grid np-pdui-grid--3">
            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Production lead time</strong><small>Heading for the automatically calculated production time.</small></div></div>
                <label class="admin-label">Heading text
                    <input class="admin-input" name="settings[production_lead_time_label]" value="{{ $settings['production_lead_time_label'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="production_lead_time_icon" label="Production heading icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Shipping method</strong><small>Heading above the shipping method cards.</small></div></div>
                <label class="admin-label">Heading text
                    <input class="admin-input" name="settings[shipping_method_label]" value="{{ $settings['shipping_method_label'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="shipping_method_icon" label="Shipping heading icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Shipping card fallback icon</strong><small>Used only when a shipping method has no image of its own.</small></div></div>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="shipping_method_card_icon" label="Fallback method icon" />
            </article>
        </div>

        <div class="np-pdui-grid np-pdui-grid--3">
            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Estimated Delivery section</strong><small>Heading shown above the delivery calculation.</small></div></div>
                <label class="admin-label">Heading text
                    <input class="admin-input" name="settings[estimated_delivery_title]" value="{{ $settings['estimated_delivery_title'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="estimated_delivery_title_icon" label="Estimated Delivery heading icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Production time</strong><small>First item in the delivery calculation.</small></div></div>
                <label class="admin-label">Label
                    <input class="admin-input" name="settings[production_time_label]" value="{{ $settings['production_time_label'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="production_time_icon" label="Production time icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Shipping time</strong><small>Second item in the delivery calculation.</small></div></div>
                <label class="admin-label">Label
                    <input class="admin-input" name="settings[shipping_time_label]" value="{{ $settings['shipping_time_label'] }}" maxlength="120">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="shipping_time_icon" label="Shipping time icon" />
            </article>
        </div>

        <div class="np-pdui-grid np-pdui-grid--3">
            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Estimated delivery result</strong><small>Final item in the delivery calculation.</small></div></div>
                <label class="admin-label">Label
                    <input class="admin-input" name="settings[estimated_delivery_label]" value="{{ $settings['estimated_delivery_label'] }}" maxlength="120">
                </label>
                <label class="admin-label">Supporting text
                    <input class="admin-input" name="settings[estimated_delivery_note]" value="{{ $settings['estimated_delivery_note'] }}" maxlength="200">
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="estimated_delivery_icon" label="Estimated delivery icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Worldwide shipping fallback</strong><small>Shown when shipping exists but no matching production rule is available.</small></div></div>
                <label class="admin-label">Heading
                    <input class="admin-input" name="settings[worldwide_shipping_title]" value="{{ $settings['worldwide_shipping_title'] }}" maxlength="120">
                </label>
                <label class="admin-label">Text
                    <textarea class="admin-textarea np-pdui-description" name="settings[worldwide_shipping_text]" maxlength="600">{{ $settings['worldwide_shipping_text'] }}</textarea>
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="worldwide_shipping_icon" label="Worldwide shipping icon" />
            </article>

            <article class="np-pdui-control-card">
                <div class="np-pdui-control-card__heading"><div><strong>Important Notes</strong><small>Each non-empty line becomes one customer-facing note.</small></div></div>
                <label class="admin-label">Heading
                    <input class="admin-input" name="settings[important_notes_title]" value="{{ $settings['important_notes_title'] }}" maxlength="120">
                </label>
                <label class="admin-label">Notes
                    <textarea class="admin-textarea np-pdui-lines" name="settings[important_notes_lines]" maxlength="1200">{{ $settings['important_notes_lines'] }}</textarea>
                </label>
                <x-admin.product-storefront-ui-icon-field :settings="$settings" setting-key="important_notes_icon" label="Important Notes icon" />
            </article>
        </div>
    </section>

</div>
