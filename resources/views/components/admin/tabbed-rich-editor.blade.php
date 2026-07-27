@props([
    'descriptionValue' => '',
    'customizationValue' => '',
    'fulfillmentValue' => '',
])

@php
    $contentSections = [
        [
            'key' => 'description',
            'name' => 'description_html',
            'label' => 'Description',
            'marker' => '[Description]',
            'note' => 'Product overview, benefits, key features, and selling points.',
            'value' => old('description_html', $descriptionValue),
        ],
        [
            'key' => 'customization',
            'name' => 'customization_artwork_html',
            'label' => 'Customization & Artwork',
            'marker' => '[Customization & Artwork]',
            'note' => 'Artwork setup, logo placement, colors, names, numbers, and design guidelines.',
            'value' => old('customization_artwork_html', $customizationValue),
        ],
        [
            'key' => 'fulfillment',
            'name' => 'fulfillment_html',
            'label' => 'Fulfillment',
            'marker' => '[Fulfillment]',
            'note' => 'Production, approval, shipping, delivery, and fulfillment details.',
            'value' => old('fulfillment_html', $fulfillmentValue),
        ],
    ];

    $initialFields = collect($contentSections)->mapWithKeys(fn ($section) => [$section['name'] => (string) ($section['value'] ?? '')])->all();
@endphp

@once
    <style>
        .np-product-content-single {
            display: grid;
            gap: 14px;
        }
        .np-product-content-single__intro {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .np-product-content-single__title {
            display: block;
            color: #1f2937;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
        }
        .np-product-content-single__help {
            margin-top: 5px;
            max-width: 920px;
            color: #64748b;
            font-size: 13px;
            font-weight: 450;
            line-height: 1.6;
        }
        .np-product-content-single__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        .np-product-content-single__guide {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
            padding: 9px;
        }
        .np-product-content-single__guide-label {
            padding: 0 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .np-product-content-single__jump {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #ffffff;
            color: #475569;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 650;
            line-height: 1;
            transition: border-color .15s ease, box-shadow .15s ease, color .15s ease, background .15s ease;
        }
        .np-product-content-single__jump:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
        }
        .np-product-content-single__jump-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #cbd5e1;
        }
        .np-product-content-single__jump.is-filled {
            border-color: #bbf7d0;
            color: #166534;
            background: #f0fdf4;
        }
        .np-product-content-single__jump.is-filled .np-product-content-single__jump-dot {
            background: #16a34a;
        }
        .np-product-content-single__editor-wrap {
            overflow: hidden;
            border: 1px solid #d9e3ef;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .035);
        }
        .np-product-content-single__toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            border-bottom: 1px solid #e5edf6;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .np-product-content-single__surface {
            min-height: 500px;
            padding: 22px 24px 28px;
            color: #334155;
            font-size: 14px;
            line-height: 1.75;
            outline: none;
        }
        .np-product-content-single__surface:focus {
            box-shadow: inset 0 0 0 2px rgba(37, 99, 235, .10);
        }
        .np-product-content-single__surface:empty:before {
            content: attr(data-placeholder);
            color: #94a3b8;
        }
        .np-product-content-single__marker {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 26px 0 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
            user-select: none;
        }
        .np-product-content-single__marker:first-child {
            margin-top: 0;
            border-top: 0;
            padding-top: 0;
        }
        .np-product-content-single__marker-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e3a8a;
            padding: 7px 11px;
            font-size: 13px;
            font-weight: 750;
            line-height: 1;
        }
        .np-product-content-single__marker-label::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #2563eb;
        }
        .np-product-content-single__marker-note {
            color: #64748b;
            font-size: 12px;
            font-weight: 450;
            line-height: 1.45;
        }
        .np-product-content-single__surface p { margin: 0 0 12px; }
        .np-product-content-single__surface h2 { margin: 16px 0 10px; color: #0f172a; font-size: 22px; font-weight: 800; line-height: 1.25; }
        .np-product-content-single__surface h3 { margin: 14px 0 8px; color: #1e293b; font-size: 18px; font-weight: 750; line-height: 1.3; }
        .np-product-content-single__surface ul, .np-product-content-single__surface ol { margin: 0 0 14px 22px; padding: 0; }
        .np-product-content-single__surface blockquote { margin: 14px 0; border-left: 4px solid #dbeafe; padding-left: 14px; color: #475569; }
        @media (max-width: 767.98px) {
            .np-product-content-single__intro { display: grid; }
            .np-product-content-single__actions { justify-content: flex-start; }
            .np-product-content-single__guide { align-items: stretch; }
            .np-product-content-single__guide-label { width: 100%; }
            .np-product-content-single__jump { justify-content: center; }
            .np-product-content-single__surface { min-height: 460px; padding: 16px; }
            .np-product-content-single__marker { display: grid; gap: 8px; margin-top: 24px; }
            .np-product-content-single__marker-label { width: max-content; max-width: 100%; }
        }
    </style>

    <script>
        window.adminProductContentOneEditor = function (config = {}) {
            return {
                sections: config.sections || [],
                fields: config.fields || {},
                status: {},
                init() {
                    this.$nextTick(() => {
                        this.renderTemplate();
                        this.sync();
                    });
                },
                markerHtml(section) {
                    return `<div class="np-product-content-single__marker" contenteditable="false" data-np-content-marker="${section.name}"><span class="np-product-content-single__marker-label">${section.label}</span><span class="np-product-content-single__marker-note">${section.note || ''}</span></div>`;
                },
                bodyHtml(section) {
                    const html = String(this.fields[section.name] || '').trim();
                    return html || '<p><br></p>';
                },
                renderTemplate() {
                    this.$refs.editor.innerHTML = this.sections.map((section) => this.markerHtml(section) + this.bodyHtml(section)).join('');
                },
                restoreMarkers() {
                    this.sync();
                    this.renderTemplate();
                    this.sync();
                    this.$refs.editor.focus();
                },
                scrollToSection(name) {
                    const marker = Array.from(this.$refs.editor.querySelectorAll('[data-np-content-marker]'))
                        .find((node) => node.getAttribute('data-np-content-marker') === name);
                    if (marker) {
                        marker.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    this.$refs.editor.focus();
                },
                command(command, value = null) {
                    this.$refs.editor.focus();
                    document.execCommand(command, false, value);
                    this.sync();
                },
                createLink() {
                    const url = window.prompt('Enter a secure URL (https://, mailto:, tel:, /path or #anchor):');
                    if (url) this.command('createLink', url);
                },
                sync() {
                    const parsed = this.parseEditor();
                    this.sections.forEach((section) => {
                        const value = parsed[section.name] || '';
                        this.fields[section.name] = value;
                        this.status[section.name] = this.hasMeaningfulContent(value);
                        const field = this.$root.querySelector(`textarea[name="${section.name}"]`);
                        if (field) field.value = value;
                        this.$dispatch('admin-rich-editor-updated', { name: section.name, value });
                    });
                },
                parseEditor() {
                    const result = {};
                    this.sections.forEach((section) => result[section.name] = '');

                    const clone = this.$refs.editor.cloneNode(true);
                    const markerNodes = Array.from(clone.querySelectorAll('[data-np-content-marker]'))
                        .filter((marker) => Object.prototype.hasOwnProperty.call(result, marker.getAttribute('data-np-content-marker')));

                    if (!markerNodes.length) {
                        const first = this.sections[0];
                        if (first) result[first.name] = this.cleanSectionHtml(clone.innerHTML);
                        return result;
                    }

                    markerNodes.forEach((marker) => {
                        const name = marker.getAttribute('data-np-content-marker');
                        const nodes = [];
                        let current = marker.nextSibling;

                        while (current) {
                            if (current.nodeType === 1 && current.hasAttribute('data-np-content-marker')) break;
                            nodes.push(current.cloneNode(true));
                            current = current.nextSibling;
                        }

                        const wrapper = document.createElement('div');
                        nodes.forEach((node) => wrapper.appendChild(node));
                        result[name] = this.cleanSectionHtml(wrapper.innerHTML);
                    });

                    return result;
                },
                cleanSectionHtml(html = '') {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = String(html || '');
                    wrapper.querySelectorAll('[data-np-content-marker]').forEach((node) => node.remove());

                    const text = (wrapper.textContent || '').replace(/\u00a0/g, ' ').trim();
                    const hasRichContent = wrapper.querySelector('img, table, ul, ol, blockquote, h1, h2, h3, h4, h5, h6');

                    if (!text && !hasRichContent) return '';

                    return wrapper.innerHTML.trim();
                },
                hasMeaningfulContent(html = '') {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = String(html || '');
                    return Boolean((wrapper.textContent || '').replace(/\u00a0/g, ' ').trim() || wrapper.querySelector('img, table, ul, ol, blockquote'));
                },
            };
        };
    </script>
@endonce

<div
    class="np-product-content-single"
    x-data="adminProductContentOneEditor({ sections: @js($contentSections), fields: @js($initialFields) })"
    x-init="init()"
    @submit.window="sync()"
>
    <div class="np-product-content-single__intro">
        <div>
            <label class="np-product-content-single__title">Product Content</label>
            <p class="np-product-content-single__help">
                Write all product tab content in one editor. Type below each section heading; the system will separate and save the content into the correct storefront tab.
            </p>
        </div>
        <div class="np-product-content-single__actions">
            <button type="button" class="np-secondary-button" @click="restoreMarkers()">Reset headings</button>
        </div>
    </div>

    <div class="np-product-content-single__guide" aria-label="Product content sections">
        <span class="np-product-content-single__guide-label">Quick sections</span>
        @foreach($contentSections as $section)
            <button type="button" class="np-product-content-single__jump" :class="{ 'is-filled': status[@js($section['name'])] }" @click="scrollToSection(@js($section['name']))">
                <span class="np-product-content-single__jump-dot"></span>
                {{ $section['label'] }}
                <span x-text="status[@js($section['name'])] ? '✓' : ''"></span>
            </button>
        @endforeach
    </div>

    <div class="np-product-content-single__editor-wrap">
        <div class="np-product-content-single__toolbar">
            <button type="button" class="admin-editor-button" @click="command('formatBlock', 'h2')">H2</button>
            <button type="button" class="admin-editor-button" @click="command('formatBlock', 'h3')">H3</button>
            <button type="button" class="admin-editor-button font-black" aria-label="Bold" title="Bold" @click="command('bold')">B</button>
            <button type="button" class="admin-editor-button italic" aria-label="Italic" title="Italic" @click="command('italic')">I</button>
            <button type="button" class="admin-editor-button underline" aria-label="Underline" title="Underline" @click="command('underline')">U</button>
            <button type="button" class="admin-editor-button" @click="command('insertUnorderedList')">• List</button>
            <button type="button" class="admin-editor-button" @click="command('insertOrderedList')">1. List</button>
            <button type="button" class="admin-editor-button" @click="command('formatBlock', 'blockquote')">Quote</button>
            <button type="button" class="admin-editor-button" @click="createLink()">Link</button>
            <button type="button" class="admin-editor-button" @click="command('removeFormat')">Clear</button>
        </div>

        <div
            x-ref="editor"
            contenteditable="true"
            @input="sync()"
            @blur="sync()"
            class="admin-rich-editor np-product-content-single__surface"
            role="textbox"
            aria-multiline="true"
            aria-label="Product Content editor"
            data-placeholder="Start writing under the section headings."
        ></div>
    </div>

    @foreach($contentSections as $section)
        <textarea name="{{ $section['name'] }}" hidden>{{ $section['value'] }}</textarea>
    @endforeach
</div>
