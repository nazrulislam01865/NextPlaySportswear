<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Http\Requests\Catalog\ProductCatalogFilterRequest;

/**
 * API-specific product catalog query contract.
 *
 * The shared base is presentation-neutral and is also used by the legacy Blade
 * request, preventing filter semantics from drifting while both paths coexist.
 */
final class ProductIndexRequest extends ProductCatalogFilterRequest
{
}
