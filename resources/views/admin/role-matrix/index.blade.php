<x-layouts.admin title="Role Matrix" eyebrow="Access Control" subtitle="Control which admin roles can view or manage each section.">
    <div class="mb-6 grid gap-4 lg:grid-cols-[1fr_360px]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-brand-ink">Permission matrix</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Super Admin always keeps full access. Delete Records is protected and can only be granted by a Super Admin.</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-white">Admin Users</a>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <h2 class="text-lg font-black text-brand-ink">Add role</h2>
            @if($canManageRoleMatrix)
                <form method="POST" action="{{ route('admin.role-matrix.roles.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="admin-label">
                        Role name
                        <input name="name" value="{{ old('name') }}" class="admin-input" maxlength="100" placeholder="Warehouse Staff" required>
                    </label>
                    <label class="admin-label">
                        Description
                        <textarea name="description" class="admin-textarea min-h-[86px]" maxlength="500" placeholder="What this role can do.">{{ old('description') }}</textarea>
                    </label>
                    <button class="btn btn-red w-full">Create Role</button>
                </form>
            @else
                <p class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">You can view this matrix, but you cannot create roles or update permissions.</p>
            @endif
        </section>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-black">Please correct the highlighted information.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.role-matrix.update') }}" class="space-y-5">
        @csrf
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="admin-table-scroll" tabindex="0" aria-label="Role permission matrix table">
                <table class="admin-table min-w-[1180px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-50 px-5 py-4">Permission</th>
                            @foreach($roles as $role)
                                <th class="px-4 py-4 text-center align-top">
                                    <span class="block whitespace-nowrap text-brand-ink">{{ $role->name }}</span>
                                    <span class="mt-1 block text-[10px] font-bold normal-case tracking-normal text-slate-400">{{ number_format((int) ($roleUserCounts[$role->slug] ?? 0)) }} users</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    @foreach($permissionsByModule as $module => $modulePermissions)
                        <tbody class="divide-y divide-slate-100">
                            <tr class="bg-slate-100/70">
                                <td colspan="{{ $roles->count() + 1 }}" class="px-5 py-3 text-xs font-black uppercase tracking-[.18em] text-brand-blue">{{ $module }}</td>
                            </tr>
                            @foreach($modulePermissions as $permission)
                                <tr>
                                    <td class="sticky left-0 z-10 bg-white px-5 py-4">
                                        <div class="max-w-[380px]">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $permission->action }}</span>
                                                <strong class="text-brand-ink">{{ $permission->label }}</strong>
                                            </div>
                                            @if($permission->description)
                                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $permission->description }}</p>
                                            @endif
                                            <code class="mt-2 inline-block rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">{{ $permission->key }}</code>
                                        </div>
                                    </td>
                                    @foreach($roles as $role)
                                        @php
                                            $checked = $role->isSuperAdmin() || (bool) ($permissionMatrix[$role->id][$permission->key] ?? false);
                                            $isDeletePermission = $permission->key === \App\Support\AdminRbac::DELETE_PERMISSION_KEY;
                                            $disabled = ! $canManageRoleMatrix || $role->isSuperAdmin() || ($isDeletePermission && ! $canManageDeletePermission);
                                        @endphp
                                        <td class="px-4 py-4 text-center align-middle">
                                            <label class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-brand-blue {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }}">
                                                <input
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-slate-300 text-brand-blue"
                                                    name="permissions[{{ $role->id }}][]"
                                                    value="{{ $permission->key }}"
                                                    @checked($checked)
                                                    @disabled($disabled)
                                                >
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>

        @if($canManageRoleMatrix)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <p class="text-sm font-semibold text-slate-500">Changes apply immediately after saving.</p>
                <button class="btn btn-red">Save Role Matrix</button>
            </div>
        @endif
    </form>
</x-layouts.admin>
