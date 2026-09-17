<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Auth\CustomerSessionResource;
use Illuminate\Http\Request;

final class CurrentCustomerController extends Controller
{
    public function __invoke(Request $request): CustomerSessionResource
    {
        return new CustomerSessionResource($request->user('web'));
    }
}
