<x-layouts.admin title="Jersey Customization Options">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-500">
            Manage reusable jersey values for Neck and Collar, Fabric, Color, Sleeves and Cuffs, and Jersey Style.
        </p>
        <a href="{{ route('admin.jersey-customization-options.create') }}" class="btn btn-red">+ Add Option</a>
    </div>

    <form
        class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card md:grid-cols-[minmax(0,1fr)_240px_auto]"
        method="GET"
    >
        <input
            class="admin-input mt-0"
            name="q"
            value="{{ $filters['q'] ?? '' }}"
            placeholder="Search option name"
        >

        <select class="admin-input mt-0" name="type">
            <option value="">All types</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <button class="btn btn-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Jersey customization options table">
            <table class="admin-table min-w-[920px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Name</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4">Value</th>
                        <th class="px-5 py-4">Description</th>
                        <th class="px-5 py-4">Image</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($options as $option)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="text-brand-ink">{{ $option->name }}</strong>
                            </td>

                            <td class="px-5 py-4 font-semibold text-slate-700">
                                {{ $option->type->label() }}
                            </td>

                            <td class="px-5 py-4">
                                @if($option->color_hex)
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-5 w-5 rounded-full border border-slate-300"
                                            style="background: {{ $option->color_hex }}"
                                        ></span>
                                        <span>{{ $option->color_hex }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="max-w-sm px-5 py-4 text-slate-600">
                                <span class="line-clamp-2">
                                    {{ $option->description ?: '—' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                        @if($option->primaryImage?->publicUrl())
                                            <img
                                                class="h-full w-full object-cover"
                                                src="{{ $option->primaryImage->publicUrl() }}"
                                                alt="{{ $option->primaryImage->name }}"
                                            >
                                        @else
                                            <span class="text-[10px] font-bold text-slate-400">None</span>
                                        @endif
                                    </div>
                                    @if($option->images_count > 1)
                                        <span class="text-xs font-semibold text-slate-500">
                                            +{{ $option->images_count - 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a
                                        class="admin-row-action border-slate-200"
                                        href="{{ route('admin.jersey-customization-options.edit', $option) }}"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.jersey-customization-options.destroy', $option) }}"
                                        onsubmit="return confirm('Delete this jersey customization option?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-slate-500">
                                No jersey customization options found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $options->links() }}</div>
</x-layouts.admin>
