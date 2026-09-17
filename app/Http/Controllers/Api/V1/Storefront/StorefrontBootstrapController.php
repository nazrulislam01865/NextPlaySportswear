<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Storefront\StorefrontBootstrapResource;
use App\Models\User;
use App\Services\Storefront\StorefrontBootstrapService;
use Illuminate\Http\Request;

final class StorefrontBootstrapController extends Controller
{
    public function __invoke(
        Request $request,
        StorefrontBootstrapService $bootstrap,
    ): StorefrontBootstrapResource {
        $user = $request->user('web');

        return new StorefrontBootstrapResource(
            $bootstrap->get($user instanceof User ? $user : null)
        );
    }
}
