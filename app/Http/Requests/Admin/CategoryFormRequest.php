<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SafePublicUrl;
use Illuminate\Validation\Rule;

class CategoryFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn(array_filter([$categoryId]))],
            'name' => ['required', 'string', 'max:160'],
            'menu_label' => ['nullable', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'category_type' => ['required', Rule::in(['standard', 'sport', 'collection', 'apparel', 'accessory', 'promotional', 'sale', 'new-arrival', 'navigation-only'])],
            'page_template' => ['required', Rule::in(['product_grid', 'sport_landing', 'collection_landing', 'image_focused', 'quote_only', 'content_landing', 'navigation_only'])],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'eyebrow' => ['nullable', 'string', 'max:160'],
            'short_title' => ['nullable', 'string', 'max:160'],
            'short_description' => ['nullable', 'string', 'max:1500'],
            'description_html' => ['nullable', 'string', 'max:150000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'best_for' => ['nullable', 'string', 'max:5000'],
            'cta_label' => ['required', 'string', 'max:160'],
            'highlights_text' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'published_at' => ['nullable', 'date'],
            'default_product_sort' => ['required', Rule::in(['featured', 'newest', 'price-low', 'price-high', 'name-asc'])],

            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'thumbnail_url' => ['nullable', 'url:http,https', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'thumbnail_alt' => ['nullable', 'string', 'max:255'],
            'remove_thumbnail' => ['nullable', 'boolean'],
            'banner_url' => ['nullable', 'url:http,https', 'max:2048'],
            'banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'banner_alt' => ['nullable', 'string', 'max:255'],
            'remove_banner' => ['nullable', 'boolean'],
            'mobile_banner_url' => ['nullable', 'url:http,https', 'max:2048'],
            'mobile_banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'mobile_banner_alt' => ['nullable', 'string', 'max:255'],
            'remove_mobile_banner' => ['nullable', 'boolean'],

            'is_visible_in_catalog' => ['nullable', 'boolean'],
            'is_visible_in_menu' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'show_product_count' => ['nullable', 'boolean'],
            'include_descendant_products' => ['nullable', 'boolean'],

            'filter_settings' => ['nullable', 'array', 'max:100'],
            'filter_settings.*.enabled' => ['nullable', 'boolean'],
            'filter_settings.*.label' => ['nullable', 'string', 'max:160'],
            'filter_settings.*.is_expanded' => ['nullable', 'boolean'],
            'filter_settings.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            'content_blocks' => ['nullable', 'array', 'max:100'],
            'content_blocks.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'content_blocks.*.block_type' => ['nullable', Rule::in(['rich_text', 'image_text', 'promo_banner', 'featured_products', 'selected_products', 'child_categories', 'highlights', 'video', 'faq', 'cta', 'logo_list', 'trust_badges', 'related_categories'])],
            'content_blocks.*.heading' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.subheading' => ['nullable', 'string', 'max:500'],
            'content_blocks.*.content_html' => ['nullable', 'string', 'max:150000'],
            'content_blocks.*.image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'content_blocks.*.image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'content_blocks.*.image_alt' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.button_label' => ['nullable', 'string', 'max:160'],
            'content_blocks.*.button_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'content_blocks.*.settings_json' => ['nullable', 'json', 'max:50000'],
            'content_blocks.*.is_active' => ['nullable', 'boolean'],

            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer_html' => ['nullable', 'string', 'max:20000'],
            'faqs.*.is_active' => ['nullable', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:2000'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'og_image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'og_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'remove_og_image' => ['nullable', 'boolean'],
            'robots_index' => ['nullable', 'boolean'],
            'robots_follow' => ['nullable', 'boolean'],
            'schema_json_text' => ['nullable', 'json', 'max:50000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'is_visible_in_catalog', 'is_visible_in_menu', 'is_featured', 'show_product_count',
            'include_descendant_products', 'robots_index', 'robots_follow', 'remove_image',
            'remove_thumbnail', 'remove_banner', 'remove_mobile_banner', 'remove_og_image',
        ];

        $payload = [];
        foreach ($booleanFields as $field) {
            $payload[$field] = $this->boolean($field);
        }

        $payload['filter_settings'] = collect($this->input('filter_settings', []))
            ->map(function ($setting): array {
                $setting = is_array($setting) ? $setting : [];
                $setting['enabled'] = filter_var($setting['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $setting['is_expanded'] = filter_var($setting['is_expanded'] ?? false, FILTER_VALIDATE_BOOLEAN);
                return $setting;
            })->all();

        $payload['content_blocks'] = collect($this->input('content_blocks', []))
            ->map(function ($block): array {
                $block = is_array($block) ? $block : [];
                $block['is_active'] = filter_var($block['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
                return $block;
            })->values()->all();

        $payload['faqs'] = collect($this->input('faqs', []))
            ->map(function ($faq): array {
                $faq = is_array($faq) ? $faq : [];
                $faq['is_active'] = filter_var($faq['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
                return $faq;
            })->values()->all();

        $this->merge($payload);
    }
}
