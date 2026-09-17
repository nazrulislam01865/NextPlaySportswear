<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Catalog\ProductCatalogFilterRequest;

/**
 * Legacy Blade catalog request. The shared filter contract is also consumed by
 * the API-specific request so both presentation paths remain behaviorally
 * identical during migration.
 */
final class ProductFilterRequest extends ProductCatalogFilterRequest
{
}
