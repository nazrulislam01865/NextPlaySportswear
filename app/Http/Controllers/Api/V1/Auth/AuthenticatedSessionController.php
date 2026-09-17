<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\ActionResource;
use App\Http\Resources\Api\V1\Auth\CustomerSessionResource;
use App\Services\Auth\CustomerSessionService;
use Illuminate\Http\Request;

final class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request, CustomerSessionService $sessions): CustomerSessionResource
    {
        return new CustomerSessionResource($sessions->login($request, $request->validated()));
    }

    public function destroy(Request $request, CustomerSessionService $sessions): ActionResource
    {
        $sessions->logout($request);
        return new ActionResource(['message' => 'Signed out successfully.']);
    }
}
