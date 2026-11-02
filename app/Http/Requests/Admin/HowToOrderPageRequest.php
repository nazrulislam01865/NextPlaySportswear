<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HowToOrderPageRequest extends FormRequest
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
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],

            'sections.01.title' => ['required', 'string', 'max:255'],
            'sections.01.description' => ['required', 'string', 'max:1000'],
            'sections.01.search_placeholder' => ['required', 'string', 'max:255'],
            'sections.01.browse_text' => ['required', 'string', 'max:120'],
            'sections.01.browse_url' => ['required', 'string', 'max:255'],

            'sections.02.title' => ['required', 'string', 'max:255'],
            'sections.02.description' => ['required', 'string', 'max:1000'],

            'sections.03.title' => ['required', 'string', 'max:255'],
            'sections.03.p1' => ['required', 'string', 'max:1000'],
            'sections.03.p2' => ['required', 'string', 'max:1000'],
            'sections.03.btn_text' => ['required', 'string', 'max:120'],

            'sections.04.title' => ['required', 'string', 'max:255'],
            'sections.04.description' => ['required', 'string', 'max:1000'],

            'sections.05.title' => ['required', 'string', 'max:255'],
            'sections.05.intro' => ['required', 'string', 'max:1000'],
            'sections.05.ship_together_label' => ['required', 'string', 'max:120'],
            'sections.05.ship_together_desc' => ['required', 'string', 'max:500'],
            'sections.05.ship_separately_label' => ['required', 'string', 'max:120'],
            'sections.05.ship_separately_desc' => ['required', 'string', 'max:500'],
            'sections.05.p1' => ['required', 'string', 'max:1000'],
            'sections.05.p2' => ['required', 'string', 'max:1000'],

            'sections.06.title' => ['required', 'string', 'max:255'],
            'sections.06.description' => ['required', 'string', 'max:1000'],

            'sections.07.title' => ['required', 'string', 'max:255'],
            'sections.07.description' => ['required', 'string', 'max:1000'],
            'sections.07.notice' => ['required', 'string', 'max:1000'],

            'sections.08.title' => ['required', 'string', 'max:255'],
            'sections.08.p1' => ['required', 'string', 'max:1000'],
            'sections.08.formula' => ['required', 'string', 'max:1000'],
            'sections.08.p2' => ['required', 'string', 'max:1000'],
            'sections.08.p3' => ['required', 'string', 'max:1000'],
            'sections.08.link_1_text' => ['required', 'string', 'max:120'],
            'sections.08.link_1_url' => ['required', 'string', 'max:255'],
            'sections.08.link_2_text' => ['required', 'string', 'max:120'],
            'sections.08.link_2_url' => ['required', 'string', 'max:255'],
            'sections.08.btn_track_text' => ['required', 'string', 'max:120'],
            'sections.08.btn_track_url' => ['required', 'string', 'max:255'],
            'sections.08.link_3_text' => ['required', 'string', 'max:120'],
            'sections.08.link_3_url' => ['required', 'string', 'max:255'],

            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

