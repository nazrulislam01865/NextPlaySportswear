<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Catalog\CategoryCatalogFilterRequest;

/** Legacy Blade category-filter request backed by the shared catalog contract. */
final class CategoryFilterRequest extends CategoryCatalogFilterRequest
{
}
