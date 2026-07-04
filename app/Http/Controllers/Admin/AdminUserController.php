<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        AdminRbac::syncDefaults(false);

        $roles = $this->assignableRoles();
        $allAdminRoleSlugs = AdminRole::query()->pluck('slug')->all();

        $users = User::query()
            ->whereIn('role', $allAdminRoleSlugs)
            ->orderByRaw("FIELD(role, 'super_admin', 'admin', 'catalog_manager', 'order_manager', 'support_agent', 'content_manager')")
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'canManageUsers' => auth('admin')->user()?->canAdmin('users.manage') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $roleSlugs = $this->assignableRoles()->pluck('slug')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roleSlugs)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Admin user created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $roleSlugs = $this->assignableRoles()->pluck('slug')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in($roleSlugs)],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $isActive = $request->boolean('is_active');

        if ($this->wouldRemoveLastActiveSuperAdmin($user, $validated['role'], $isActive)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['role' => 'At least one active Super Admin must remain.']);
        }

        $user->forceFill([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Admin user updated successfully.');
    }

    private function assignableRoles()
    {
        $query = AdminRole::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! auth('admin')->user()?->isSuperAdmin()) {
            $query->where('slug', '!=', 'super_admin');
        }

        return $query->get();
    }

    private function wouldRemoveLastActiveSuperAdmin(User $user, string $newRole, bool $newIsActive): bool
    {
        $willStillBeSuperAdmin = $newRole === 'super_admin' && $newIsActive;

        if ($willStillBeSuperAdmin) {
            return false;
        }

        if ($user->role !== 'super_admin') {
            return false;
        }

        return User::query()
            ->where('id', '!=', $user->id)
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->doesntExist();
    }
}
