<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth('admin')->user()?->canAdmin('payment_page.manage') ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Links are local paths only: no javascript:, protocol-relative, or external redirects.
        $localPath = ['required', 'string', 'max:500', 'regex:~^/(?!/)[^\s]*$~'];

        return [
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],
            'navigation.home_label' => ['required', 'string', 'max:100'],
            'navigation.breadcrumb_label' => ['nullable', 'string', 'max:100'],
            'navigation.sidebar_heading' => ['required', 'string', 'max:100'],
            'sections' => ['required', 'array', 'size:8'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.nav_label' => ['required', 'string', 'max:100'],
            'sections.*.content' => ['nullable', 'string', 'max:3000'],
            'status_table.columns' => ['required', 'array', 'min:1', 'max:10'],
            'status_table.columns.*' => ['required', 'string', 'max:100'],
            'status_table.rows' => ['required', 'array', 'min:1', 'max:100'],
            'status_table.rows.*.cells' => ['required', 'array', 'min:1', 'max:10'],
            'status_table.rows.*.cells.0' => ['required', 'string', 'max:500'],
            'status_table.rows.*.cells.*' => ['nullable', 'string', 'max:500'],
            'refund_link.label' => ['required', 'string', 'max:160'],
            'refund_link.href' => $localPath,
            'support.title' => ['required', 'string', 'max:255'],
            'support.description' => ['required', 'string', 'max:1000'],
            'support.primary_label' => ['required', 'string', 'max:100'],
            'support.primary_href' => $localPath,
            'support.secondary_label' => ['required', 'string', 'max:100'],
            'support.secondary_href' => $localPath,
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $columns = $this->input('status_table.columns');
            $rows = $this->input('status_table.rows');
            if (! is_array($columns) || ! is_array($rows)) {
                return;
            }

            foreach ($rows as $index => $row) {
                $cells = is_array($row) ? ($row['cells'] ?? null) : null;
                if (is_array($cells) && count($cells) !== count($columns)) {
                    $validator->errors()->add("status_table.rows.{$index}.cells", 'Each table row must have a cell for every column.');
                }
            }
        });
    }
}
