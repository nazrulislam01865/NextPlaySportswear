<x-layouts.admin title="Admin Users" eyebrow="Access Control" subtitle="Create admin accounts and assign role-matrix access.">
    <div class="mb-6 grid gap-4 xl:grid-cols-[420px_1fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-brand-ink">Create admin user</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">New users can sign in from the admin login page.</p>
                </div>
                <a href="{{ route('admin.role-matrix.index') }}" class="btn btn-white">Role Matrix</a>
            </div>

            @if($canManageUsers)
                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <label class="admin-label">
                        Name
                        <input name="name" value="{{ old('name') }}" class="admin-input" maxlength="255" required>
                    </label>
                    <label class="admin-label">
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" class="admin-input" maxlength="255" required>
                    </label>
                    <label class="admin-label">
                        Role
                        <select name="role" class="admin-input" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" @selected(old('role') === $role->slug)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="admin-label">
                            Password
                            <input type="password" name="password" class="admin-input" minlength="8" required>
                        </label>
                        <label class="admin-label">
                            Confirm password
                            <input type="password" name="password_confirmation" class="admin-input" minlength="8" required>
                        </label>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                        Active
                    </label>
                    <button class="btn btn-red w-full">Create Admin User</button>
                </form>
            @else
                <p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">You can view admin users, but you cannot create or update them.</p>
            @endif
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-xl font-black text-brand-ink">Administrators</h2>
                <p class="mt-1 text-sm text-slate-500">Update role, active status, name or password.</p>
            </div>

            @if ($errors->any())
                <div class="m-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-black">Please correct the highlighted information.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="admin-table-scroll" tabindex="0" aria-label="Admin users table">
                <table class="admin-table min-w-[980px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">User</th>
                            <th class="px-5 py-4">Role</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Last login</th>
                            <th class="px-5 py-4 text-right">Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-5 py-4 align-top">
                                    @if($canManageUsers)
                                        <form id="admin-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <input name="name" value="{{ old('name', $user->name) }}" class="admin-input h-11" maxlength="255" required>
                                            <p class="text-xs font-semibold text-slate-500">{{ $user->email }}</p>
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <input type="password" name="password" class="admin-input h-10 text-xs" minlength="8" placeholder="New password">
                                                <input type="password" name="password_confirmation" class="admin-input h-10 text-xs" minlength="8" placeholder="Confirm password">
                                            </div>
                                        </form>
                                    @else
                                        <strong class="block text-brand-ink">{{ $user->name }}</strong>
                                        <span class="text-xs text-slate-500">{{ $user->email }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    @if($canManageUsers)
                                        <select name="role" form="admin-user-{{ $user->id }}" class="admin-input h-11 min-w-[190px]">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->slug }}" @selected($user->role === $role->slug)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="font-bold">{{ $user->adminRoleLabel() }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    @if($canManageUsers)
                                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-black">
                                            <input type="hidden" name="is_active" form="admin-user-{{ $user->id }}" value="0">
                                            <input type="checkbox" name="is_active" form="admin-user-{{ $user->id }}" value="1" @checked($user->is_active) class="h-5 w-5 rounded border-slate-300 text-brand-blue">
                                            Active
                                        </label>
                                    @else
                                        <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-slate-500">
                                    {{ $user->last_login_at?->format('M d, Y · g:i A') ?: 'Never' }}
                                </td>
                                <td class="px-5 py-4 text-right align-top">
                                    @if($canManageUsers)
                                        <button form="admin-user-{{ $user->id }}" class="btn btn-white">Save</button>
                                    @else
                                        <span class="text-xs text-slate-400">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No admin users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-5">{{ $users->links('pagination.nextplay') }}</div>
        </section>
    </div>
</x-layouts.admin>
