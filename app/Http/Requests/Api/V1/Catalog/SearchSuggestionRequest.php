<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Http\Requests\Api\V1\ApiFormRequest;

final class SearchSuggestionRequest extends ApiFormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function queryText(): string
    {
        return trim((string) ($this->validated('q') ?? ''));
    }

    public function resultLimit(): int
    {
        return max(1, min(12, (int) ($this->validated('limit') ?? 8)));
    }
}
