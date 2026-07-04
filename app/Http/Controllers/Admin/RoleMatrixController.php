<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleMatrixController extends Controller
{
    public function index(): View
    {
        AdminRbac::syncDefaults(false);

        $permissions = AdminPermission::query()
            ->orderBy('sort_order')
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->groupBy('module');

        $roles = AdminRole::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $roleUserCounts = DB::table('users')
            ->select('role', DB::raw('count(*) as aggregate'))
            ->whereIn('role', $roles->pluck('slug')->all())
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $matrix = [];
        DB::table('admin_role_permissions')
            ->join('admin_permissions', 'admin_permissions.id', '=', 'admin_role_permissions.permission_id')
            ->select('admin_role_permissions.role_id', 'admin_permissions.key', 'admin_role_permissions.allowed')
            ->get()
            ->each(function ($row) use (&$matrix): void {
                $matrix[(int) $row->role_id][(string) $row->key] = (bool) $row->allowed;
            });

        return view('admin.role-matrix.index', [
            'permissionsByModule' => $permissions,
            'roles' => $roles,
            'permissionMatrix' => $matrix,
            'roleUserCounts' => $roleUserCounts,
            'canManageRoleMatrix' => auth('admin')->user()?->canAdmin('role_matrix.manage') ?? false,
            'canManageDeletePermission' => auth('admin')->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('admin_roles', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated): void {
            $baseSlug = Str::slug($validated['name']);
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'role';
            $slug = $baseSlug;
            $suffix = 2;

            while (AdminRole::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $role = AdminRole::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'sort_order' => ((int) AdminRole::query()->max('sort_order')) + 10,
                'is_system' => false,
                'is_active' => true,
            ]);

            $now = now();
            AdminPermission::query()->pluck('id')->each(function ($permissionId) use ($role, $now): void {
                DB::table('admin_role_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'allowed' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        });

        return redirect()
            ->route('admin.role-matrix.index')
            ->with('status', 'Role created successfully. Select its permissions from the matrix and save.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['string'],
        ]);

        $permissionInput = $validated['permissions'] ?? [];
        $canManageDeletePermission = $request->user('admin')?->isSuperAdmin() ?? false;

        DB::transaction(function () use ($permissionInput, $canManageDeletePermission): void {
            $permissions = AdminPermission::query()->orderBy('sort_order')->get();
            $validPermissionKeys = $permissions->pluck('key')->map(fn ($key) => (string) $key)->all();
            $roles = AdminRole::query()->where('is_active', true)->orderBy('sort_order')->get();
            $now = now();

            foreach ($roles as $role) {
                $existingAllowedByPermissionId = DB::table('admin_role_permissions')
                    ->where('role_id', $role->id)
                    ->pluck('allowed', 'permission_id');

                $allowedKeys = collect($permissionInput[$role->id] ?? [])
                    ->map(fn ($key) => (string) $key)
                    ->filter(fn (string $key): bool => in_array($key, $validPermissionKeys, true))
                    ->values()
                    ->all();

                foreach ($permissions as $permission) {
                    if ($permission->key === AdminRbac::DELETE_PERMISSION_KEY) {
                        $allowed = $role->isSuperAdmin()
                            || ($canManageDeletePermission
                                ? in_array($permission->key, $allowedKeys, true)
                                : (bool) ($existingAllowedByPermissionId[$permission->id] ?? false));
                    } else {
                        $allowed = $role->isSuperAdmin() || in_array($permission->key, $allowedKeys, true);
                    }

                    DB::table('admin_role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        [
                            'allowed' => $allowed,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });

        return redirect()
            ->route('admin.role-matrix.index')
            ->with('status', 'Role permissions updated successfully.');
    }
}
