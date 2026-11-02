<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;

use Illuminate\Foundation\Http\FormRequest;

class ArtworkGuidelinesPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero.eyebrow' => ['nullable', 'string', 'max:120'],
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],

            'section_01.title' => ['required', 'string', 'max:255'],
            'section_01.notice' => ['required', 'string', 'max:1000'],
            'section_01.formats' => ['required', 'array'],
            'section_01.formats.*.name' => ['required', 'string', 'max:120'],
            'section_01.formats.*.badge' => ['required', 'string', 'max:50'],
            'section_01.formats.*.badge_color' => ['required', 'string', 'max:30'],
            'section_01.formats.*.description' => ['required', 'string', 'max:1000'],

            'section_02.title' => ['required', 'string', 'max:255'],
            'section_02.callout' => ['required', 'string', 'max:1000'],
            'section_02.checklist' => ['required', 'array'],
            'section_02.checklist.*' => ['required', 'string', 'max:500'],

            'section_03.title' => ['required', 'string', 'max:255'],
            'section_03.subtitle' => ['required', 'string', 'max:500'],
            'section_03.front_spec' => ['required', 'string', 'max:255'],
            'section_03.back_spec' => ['required', 'string', 'max:255'],
            'section_03.footnote' => ['required', 'string', 'max:500'],

            'section_04.title' => ['required', 'string', 'max:255'],
            'section_04.methods' => ['required', 'array'],
            'section_04.methods.*.name' => ['required', 'string', 'max:120'],
            'section_04.methods.*.ideal_for' => ['required', 'string', 'max:500'],
            'section_04.methods.*.file_types' => ['required', 'string', 'max:500'],
            'section_04.methods.*.colours' => ['required', 'string', 'max:500'],

            'section_05.title' => ['required', 'string', 'max:255'],
            'section_05.intro' => ['required', 'string', 'max:500'],
            'section_05.items' => ['required', 'array'],
            'section_05.items.*' => ['required', 'string', 'max:500'],
            'section_05.notice' => ['required', 'string', 'max:1000'],

            'section_06.title' => ['required', 'string', 'max:255'],
            'section_06.intro' => ['required', 'string', 'max:500'],
            'section_06.filename_label' => ['required', 'string', 'max:255'],
            'section_06.filename_example' => ['required', 'string', 'max:255'],
            'section_06.copyright_notice' => ['required', 'string', 'max:1000'],
            'section_06.cta_text' => ['required', 'string', 'max:255'],
            'section_06.cta_url' => ['required', 'string', 'max:255', new SafePublicUrl()],

            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

