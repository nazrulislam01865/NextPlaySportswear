<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\SearchSuggestionRequest;
use App\Http\Resources\Api\V1\Catalog\SearchSuggestionResource;
use App\Services\Catalog\CatalogReadService;

final class SearchSuggestionController extends Controller
{
    public function __invoke(SearchSuggestionRequest $request, CatalogReadService $catalog): SearchSuggestionResource
    {
        return new SearchSuggestionResource(
            $catalog->suggestions($request->queryText(), $request->resultLimit())
        );
    }
}
