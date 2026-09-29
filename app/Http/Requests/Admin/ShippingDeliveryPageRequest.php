<?php

namespace App\Http\Requests\Admin;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class ShippingDeliveryPageRequest extends FormRequest
{
    public const TAB_IDS = ['before-you-order', 'artwork-customisation', 'after-you-order', 'delivery', 'help'];
    public const INFO_CARD_IDS = ['before-you-pay', 'after-dispatch'];
    public const STEP_IDS = ['confirm-address', 'choose-delivery-option', 'follow-dispatch-updates'];
    public const FAQ_IDS = ['delivery-costs', 'change-address', 'tracking-number', 'multiple-locations'];
    public const CHECKLIST_IDS = ['recipient-name', 'full-address', 'postcode', 'contact-details'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'hero' => ['required', 'array:eyebrow,title,subtitle'],
            'hero.eyebrow' => ['required', 'string', 'max:120'],
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.subtitle' => ['required', 'string', 'max:1000'],

            'tabs' => ['required', 'array', 'size:5'],
            'tabs.*' => ['required', 'array:id,label'],
            'tabs.*.id' => ['required', 'string', 'max:100'],
            'tabs.*.label' => ['required', 'string', 'max:120'],

            'delivery_intro' => ['required', 'array:title,subtitle'],
            'delivery_intro.title' => ['required', 'string', 'max:255'],
            'delivery_intro.subtitle' => ['required', 'string', 'max:1000'],

            'info_cards' => ['required', 'array:cards'],
            'info_cards.cards' => ['required', 'array', 'size:2'],
            'info_cards.cards.*' => ['required', 'array:id,title,description,icon_alt'],
            'info_cards.cards.*.id' => ['required', 'string', 'max:100'],
            'info_cards.cards.*.title' => ['required', 'string', 'max:255'],
            'info_cards.cards.*.description' => ['required', 'string', 'max:1000'],
            'info_cards.cards.*.icon_alt' => ['required', 'string', 'max:255'],

            'delivery_steps' => ['required', 'array:title,steps'],
            'delivery_steps.title' => ['required', 'string', 'max:255'],
            'delivery_steps.steps' => ['required', 'array', 'size:3'],
            'delivery_steps.steps.*' => ['required', 'array:id,number,title,description'],
            'delivery_steps.steps.*.id' => ['required', 'string', 'max:100'],
            'delivery_steps.steps.*.number' => ['required', 'string', 'max:10'],
            'delivery_steps.steps.*.title' => ['required', 'string', 'max:255'],
            'delivery_steps.steps.*.description' => ['required', 'string', 'max:1000'],

            'notice' => ['required', 'array:text,icon_alt'],
            'notice.text' => ['required', 'string', 'max:1000'],
            'notice.icon_alt' => ['required', 'string', 'max:255'],

            'faqs' => ['required', 'array:title,subtitle,items'],
            'faqs.title' => ['required', 'string', 'max:255'],
            'faqs.subtitle' => ['required', 'string', 'max:1000'],
            'faqs.items' => ['required', 'array', 'size:4'],
            'faqs.items.*' => ['required', 'array:id,question,answer'],
            'faqs.items.*.id' => ['required', 'string', 'max:100'],
            'faqs.items.*.question' => ['required', 'string', 'max:500'],
            'faqs.items.*.answer' => ['required', 'string', 'max:2000'],

            'address_checklist' => ['required', 'array:title,subtitle,items'],
            'address_checklist.title' => ['required', 'string', 'max:255'],
            'address_checklist.subtitle' => ['required', 'string', 'max:1000'],
            'address_checklist.items' => ['required', 'array', 'size:4'],
            'address_checklist.items.*' => ['required', 'array:id,title,description,icon_alt'],
            'address_checklist.items.*.id' => ['required', 'string', 'max:100'],
            'address_checklist.items.*.title' => ['required', 'string', 'max:255'],
            'address_checklist.items.*.description' => ['required', 'string', 'max:1000'],
            'address_checklist.items.*.icon_alt' => ['required', 'string', 'max:255'],

            'cta' => ['required', 'array:title,description,primary_label,primary_url,policy_label,policy_url'],
            'cta.title' => ['required', 'string', 'max:255'],
            'cta.description' => ['required', 'string', 'max:1000'],
            'cta.primary_label' => ['required', 'string', 'max:100'],
            'cta.primary_url' => $this->destinationRules(),
            'cta.policy_label' => ['required', 'string', 'max:100'],
            'cta.policy_url' => $this->destinationRules(),

            'seo' => ['required', 'array:title,description'],
            'seo.title' => ['required', 'string', 'max:255'],
            'seo.description' => ['required', 'string', 'max:500'],

            'notice_icon' => $this->imageRules(),
            'remove_notice_icon' => ['nullable', 'boolean'],
        ];

        for ($i = 0; $i < 2; $i++) {
            $rules['info_card_icon_'.$i] = $this->imageRules();
            $rules['remove_info_card_icon_'.$i] = ['nullable', 'boolean'];
        }
        for ($i = 0; $i < 4; $i++) {
            $rules['checklist_icon_'.$i] = $this->imageRules();
            $rules['remove_checklist_icon_'.$i] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStableIds($validator, 'tabs', self::TAB_IDS);
            $this->validateStableIds($validator, 'info_cards.cards', self::INFO_CARD_IDS);
            $this->validateStableIds($validator, 'delivery_steps.steps', self::STEP_IDS);
            $this->validateStableIds($validator, 'faqs.items', self::FAQ_IDS);
            $this->validateStableIds($validator, 'address_checklist.items', self::CHECKLIST_IDS);
        });
    }

    /** @return array<string, mixed> */
    public function validatedContent(): array
    {
        return Arr::only($this->validated(), [
            'hero', 'tabs', 'delivery_intro', 'info_cards', 'delivery_steps',
            'notice', 'faqs', 'address_checklist', 'cta', 'seo',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $content = [];
        foreach (['hero', 'tabs', 'delivery_intro', 'info_cards', 'delivery_steps', 'notice', 'faqs', 'address_checklist', 'cta', 'seo'] as $key) {
            if ($this->has($key) && is_array($this->input($key))) {
                $content[$key] = $this->trimValues($this->input($key));
            }
        }

        $flags = ['remove_notice_icon'];
        for ($i = 0; $i < 2; $i++) {
            $flags[] = 'remove_info_card_icon_'.$i;
        }
        for ($i = 0; $i < 4; $i++) {
            $flags[] = 'remove_checklist_icon_'.$i;
        }
        foreach ($flags as $flag) {
            $content[$flag] = $this->boolean($flag);
        }

        $this->merge($content);
    }

    /** @return array<int, mixed> */
    private function imageRules(): array
    {
        return [
            'nullable', 'file', 'image',
            'mimes:jpg,jpeg,png,webp,avif',
            'mimetypes:image/jpeg,image/png,image/webp,image/avif',
            'max:2048',
        ];
    }

    /** @return array<int, mixed> */
    private function destinationRules(): array
    {
        return [
            'required', 'string', 'max:2048',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! PublicUrl::isAllowed($value)) {
                    $fail('The '.$attribute.' must be a safe relative site path, fragment, or valid HTTP/HTTPS URL.');
                }
            },
        ];
    }

    /** @param array<int, string> $expected */
    private function validateStableIds(Validator $validator, string $key, array $expected): void
    {
        $items = $this->input($key, []);
        if (! is_array($items) || count($items) !== count($expected)) {
            return;
        }
        $ids = array_map(
            static fn (mixed $item): ?string => is_array($item) && isset($item['id']) ? (string) $item['id'] : null,
            $items
        );
        if ($ids !== $expected) {
            $validator->errors()->add($key, 'The fixed Shipping & Delivery slots cannot be added, removed, duplicated, or reordered.');
        }
    }

    private function trimValues(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $value[$childKey] = $this->trimValues($childValue, (string) $childKey);
            }
            return $value;
        }
        if (is_string($value) && $key !== 'id') {
            return trim($value);
        }

        return $value;
    }
}
