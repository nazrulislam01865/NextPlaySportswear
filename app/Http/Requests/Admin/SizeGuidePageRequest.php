<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SizeGuidePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth('admin')->user()?->canAdmin('size_guide_page.manage') ?? false);
    }

    public function rules(): array
    {
        return [
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],
            'sections' => ['required', 'array', 'size:2'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['required', 'string', 'max:1000'],
            'sections.*.items' => ['required', 'array', 'min:1', 'max:12'],
            'sections.*.items.*' => ['required', 'string', 'max:500'],
            'charts' => ['required', 'array', 'size:3'],
            'charts.*.title' => ['required', 'string', 'max:255'],
            'charts.*.description' => ['required', 'string', 'max:1000'],
            'charts.*.rows' => ['required', 'array', 'min:1', 'max:30'],
            'charts.*.rows.*' => ['required', 'array', 'size:5'],
            'charts.*.rows.*.*' => ['required', 'string', 'max:100'],
            'fit.title' => ['required', 'string', 'max:255'],
            'fit.description' => ['required', 'string', 'max:1000'],
            'fit.tips' => ['required', 'array', 'size:2'],
            'fit.tips.*' => ['required', 'string', 'max:500'],
            'fit.cta_text' => ['required', 'string', 'max:100'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
