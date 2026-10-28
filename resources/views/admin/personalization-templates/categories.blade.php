<x-layouts.admin title="Personalization Categories" compact-header>
    <x-admin.personalization-page heading="Personalization Categories" eyebrow="Catalog / Personalization" description="Categories with configured item personalization and their customer-facing fields.">
        <x-slot:actions>
            <a href="{{ route('admin.personalization-templates.index') }}" class="pt-button pt-outline">Template Library</a>
            @if(auth('admin')->user()?->canAdmin('customization.manage'))
                <a href="{{ route('admin.personalization-templates.create') }}" class="pt-button pt-primary">＋ Add Personalization</a>
            @endif
        </x-slot:actions>
        <div class="pt-card pt-list-card">
            <div class="pt-list-head">
                <h3>Configured category list</h3>
                <span class="pt-count">{{ $categories->total() }} {{ $categories->total() === 1 ? 'category' : 'categories' }}</span>
            </div>
            <form method="GET" action="{{ route('admin.personalization-categories.index') }}" class="pt-category-filter" role="search">
                <div class="pt-filter-field">
                    <label class="pt-filter-caption" for="pt-category-search">Search categories or personalization fields</label>
                    <input class="pt-input" id="pt-category-search" type="search" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="Search category, template or field...">
                    @error('q')<p class="pt-field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="pt-button pt-primary">Search</button>
                <a class="pt-button pt-outline" href="{{ route('admin.personalization-categories.index') }}">Clear</a>
            </form>
            <div class="pt-scroll">
                <table class="pt-table pt-categories-table">
                    <thead><tr>
                        <th>#</th><th>Product category</th><th>Personalization setup</th><th>Item personalization fields</th><th>Assigned products</th><th>Status</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse($categories as $category)
                            @php
                                $templates = $category->personalizationTemplates;
                                $fieldLabels = $templates->flatMap(fn ($template) => collect($template->fields ?? [])
                                    ->filter(fn ($field) => (bool) ($field['enabled'] ?? true))
                                    ->pluck('label'))->filter()->unique()->values();
                                $activeCount = $templates->filter(fn ($template) => $template->is_published && $template->is_active)->count();
                                $draftCount = $templates->filter(fn ($template) => ! $template->is_published)->count();
                            @endphp
                            <tr>
                                <td class="pt-row-number">{{ str_pad((string) ($categories->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="pt-configured-category-name">
                                        <strong>{{ $category->name }}</strong>
                                        <span class="pt-personalization-added">Personalization Added</span>
                                    </div>
                                    <small>{{ $category->ancestors->pluck('name')->push($category->name)->implode(' / ') }}</small>
                                </td>
                                <td>
                                    @foreach($templates as $template)
                                        <div class="pt-configured-template">
                                            <a href="{{ route('admin.personalization-templates.edit', $template) }}" class="pt-strong">{{ $template->section_title ?: $template->name }}</a>
                                            <small>{{ $template->name }}</small>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="pt-tags">
                                        @forelse($fieldLabels as $label)
                                            <span>{{ $label }}</span>
                                        @empty
                                            <span>Not configured</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td><strong>{{ $templates->sum('products_count') }}</strong> <small>template assignments</small></td>
                                <td>
                                    <div class="pt-configured-status">
                                        @if($activeCount)<span class="pt-pill pt-pill-green">● {{ $activeCount }} active</span>@endif
                                        @if($draftCount)<span class="pt-pill pt-pill-gray">{{ $draftCount }} draft</span>@endif
                                        @if($templates->count() > $activeCount + $draftCount)
                                            <span class="pt-pill pt-pill-gray">{{ $templates->count() - $activeCount - $draftCount }} inactive</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="pt-configured-actions">
                                        <a class="pt-button pt-outline" href="{{ $templates->count() === 1
                                            ? route('admin.personalization-templates.edit', $templates->first())
                                            : route('admin.personalization-templates.index', ['category' => $category->id]) }}">Manage Setup</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="pt-empty">No configured personalization categories found. Add a personalization template to a product category to see it here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pt-list-foot">
                <span>Showing {{ $categories->count() }} of {{ $categories->total() }} categories</span>
                {{ $categories->links('pagination.nextplay') }}
            </div>
        </div>
    </x-admin.personalization-page>
</x-layouts.admin>
