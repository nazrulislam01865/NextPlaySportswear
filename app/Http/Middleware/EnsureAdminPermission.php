<?php

namespace App\Http\Middleware;

use App\Support\AdminRbac;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        Auth::shouldUse('admin');

        $user = Auth::guard('admin')->user();
        $routeName = $request->route()?->getName();
        $requiredPermission = $permission ?: AdminRbac::permissionForRoute($routeName, $request);

        if ($requiredPermission && ! $user?->canAdmin($requiredPermission)) {
            if ($request->expectsJson()) {
                abort(403, 'You do not have permission to access this admin section.');
            }

            $destination = AdminRbac::firstAllowedRoute($user);

            abort_if($destination === null || $destination === $request->fullUrl(), 403, 'You do not have permission to access this admin section.');

            return redirect($destination)
                ->with('status', 'You do not have permission to access that admin section.');
        }

        if (AdminRbac::requiresDeletePermission($request) && ! $user?->canDeleteAdminRecords()) {
            if ($request->expectsJson()) {
                abort(403, 'Delete permission is required.');
            }

            return redirect()->back()->with('status', 'Delete permission is required. A Super Admin can grant it from Role Matrix.');
        }

        return $next($request);
    }
}
