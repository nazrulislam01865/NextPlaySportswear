<?php

namespace App\Http\Requests\Admin;

use App\Enums\SizeAudience;
use App\Models\SizeOptionGroup;
use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SizeOptionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $group = $this->route('sizeOptionGroup');

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('size_option_groups', 'slug')->ignore($group?->getKey())],
            'audience' => ['required', Rule::enum(SizeAudience::class)],
            'description_html' => ['nullable', 'string', 'max:100000'],
            'chart_html' => ['nullable', 'string', 'max:100000'],
            'chart_image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
            'chart_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'clear_chart_image' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'sizes' => ['required', 'array', 'min:1', 'max:100'],
            'sizes.*.label' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'sizes.*.code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'sizes.*.is_active' => ['required', 'boolean'],
            'sizes.*.sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasUploadedImage = $this->file('chart_image') !== null;
            $hasLinkedImage = filled($this->input('chart_image_url'));
            $hasFormattedChart = filled(strip_tags((string) $this->input('chart_html')));

            if ($hasUploadedImage && $hasLinkedImage) {
                $validator->errors()->add('chart_image_url', 'Use either an uploaded chart image or an image link, not both.');
            }

            if ($hasFormattedChart && ($hasUploadedImage || $hasLinkedImage)) {
                $validator->errors()->add('chart_html', 'Use either formatted size-chart content or a size-chart image, not both.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $group = $this->route('sizeOptionGroup');
        $used = [];
        $sizes = collect($this->input('sizes', []))
            ->filter(fn ($row) => is_array($row) && filled($row['label'] ?? null))
            ->map(function (array $row, int $index) use (&$used): array {
                $base = Str::slug((string) ($row['code'] ?? $row['label'] ?? '')) ?: 'size-'.($index + 1);
                $code = $base;
                $suffix = 2;
                while (in_array($code, $used, true)) {
                    $code = $base.'-'.$suffix++;
                }
                $used[] = $code;

                return [
                    'label' => trim((string) $row['label']),
                    'code' => $code,
                    'is_active' => true,
                    'sort_order' => $index,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('slug', $this->input('name'))),
            'description_html' => filled($this->input('description_html')) ? (string) $this->input('description_html') : null,
            'chart_html' => filled(strip_tags((string) $this->input('chart_html'))) ? (string) $this->input('chart_html') : null,
            'chart_image_url' => filled($this->input('chart_image_url')) ? trim((string) $this->input('chart_image_url')) : null,
            'clear_chart_image' => $this->boolean('clear_chart_image'),
            'is_active' => true,
            'sort_order' => $group instanceof SizeOptionGroup ? (int) $group->sort_order : 0,
            'sizes' => $sizes,
        ]);
    }
}
