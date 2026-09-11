<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RuralAreaSurchargeImportChunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'uuid'],
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.source_row' => ['required', 'integer', 'min:1', 'max:1000000'],
            'rows.*.country' => ['required', 'string', 'max:120'],
            'rows.*.iata_code' => ['required', 'string', 'size:2', 'alpha'],
            'rows.*.postal_code_low' => ['required', 'max:32'],
            'rows.*.postal_code_high' => ['required', 'max:32'],
            'rows.*.city' => ['nullable', 'max:160'],
            'rows.*.origin_surcharge' => ['required', 'string', 'max:80'],
            'rows.*.destination_surcharge' => ['required', 'string', 'max:80'],
        ];
    }
}
