@php
    $categoryOptions = $categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->display_path])->all();
    $statusOptions = [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive'], ['value' => 'draft', 'label' => 'Draft']];
@endphp
<x-layouts.admin title="Personalization Templates" compact-header>
    <x-admin.personalization-page heading="Personalization Templates" eyebrow="Master Data / Personalization">
        <x-slot:actions><a href="{{ route('admin.personalization-templates.create') }}" class="pt-button pt-primary">＋ Add Personalization Template</a></x-slot:actions>
        @error('template')<p class="pt-field-error" role="alert">{{ $message }}</p>@enderror
        <div class="pt-card pt-list-card">
            <div class="pt-list-head"><div><h3>Personalization template library</h3></div><span class="pt-count">{{ $templates->total() }} {{ $templates->total() === 1 ? 'template' : 'templates' }}</span></div>
            <form method="GET" action="{{ route('admin.personalization-templates.index') }}" class="pt-filter" role="search">
                <div class="pt-filter-field"><label class="pt-filter-caption" for="pt-search">Search templates</label><input class="pt-input" id="pt-search" type="search" name="q" maxlength="120" placeholder="Search by template name or field..." value="{{ $filters['q'] ?? '' }}"><p class="pt-field-error">@error('q'){{ $message }}@enderror</p></div>
                <div class="pt-filter-field"><label class="pt-filter-caption" for="pt-filter-category">Product category</label><x-admin.personalization-select name="category" id="pt-filter-category" :selected="($filters['category'] ?? '')" :options="$categoryOptions" placeholder="All categories" search-placeholder="Search categories..." /><p class="pt-field-error">@error('category'){{ $message }}@enderror</p></div>
                <div class="pt-filter-field"><label class="pt-filter-caption" for="pt-filter-status">Status</label><x-admin.personalization-select name="status" id="pt-filter-status" :selected="($filters['status'] ?? '')" :options="$statusOptions" placeholder="All statuses" :searchable="false" /><p class="pt-field-error">@error('status'){{ $message }}@enderror</p></div>
                <div class="pt-filter-actions"><button type="submit" class="pt-button pt-primary">Apply filters</button><a href="{{ route('admin.personalization-templates.index') }}" class="pt-button pt-outline" aria-label="Clear all filters">Clear filters</a></div>
            </form>
            <div class="pt-scroll"><table class="pt-table"><thead><tr><th>#</th><th>Template name</th><th>Last-level category</th><th>Fields</th><th>Assigned to</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($templates as $template)
                <tr>
                    <td class="pt-row-number">{{ str_pad((string) ($templates->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT) }}</td>
                    <td><a class="pt-strong" href="{{ route('admin.personalization-templates.edit', $template) }}">{{ $template->name }}</a></td>
                    <td class="pt-category-name">{{ $template->category ? $template->category->ancestors->pluck('name')->push($template->category->name)->implode(' / ') : 'Category unavailable' }}</td>
                    <td>
                        <div class="pt-tags">
                            @forelse(collect($template->fields)->filter(fn ($field) => $field['enabled'] ?? true) as $field)
                                <span>{{ $field['label'] }}</span>
                            @empty
                                <span>Not configured</span>
                            @endforelse
                        </div>
                    </td>
                    <td><strong>{{ $template->products_count }}</strong> {{ $template->products_count === 1 ? 'product' : 'products' }}</td>
                    <td>
                        @if(!$template->is_published)
                            <span class="pt-pill pt-pill-gray">Draft</span>
                        @elseif($template->is_active)
                            <span class="pt-pill pt-pill-green">● Active</span>
                        @else
                            <span class="pt-pill pt-pill-gray">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="pt-row-actions">
                            <a href="{{ route('admin.personalization-templates.edit', $template) }}" title="Edit" aria-label="Edit {{ $template->name }}">✎</a>
                            <form method="POST" action="{{ route('admin.personalization-templates.duplicate', $template) }}">
                                @csrf
                                <button type="submit" title="Duplicate" aria-label="Duplicate {{ $template->name }}">▣</button>
                            </form>
                            <details class="pt-menu">
                                <summary aria-label="More actions for {{ $template->name }}">⋯</summary>
                                <div>
                                    <a href="{{ route('admin.personalization-templates.fields', $template) }}">Configure fields</a>
                                    @if($template->fields)
                                        <a href="{{ route('admin.personalization-templates.preview', $template) }}">Preview</a>
                                    @endif
                                    @if(!$template->products_count)
                                        <form method="POST" action="{{ route('admin.personalization-templates.destroy', $template) }}" onsubmit="return confirm('Delete this unassigned template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Delete template</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="pt-empty">No templates found. Create a template to start collecting the right text and number details.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="pt-list-foot"><span>Showing {{ $templates->count() }} of {{ $templates->total() }} templates</span>{{ $templates->links('pagination.nextplay') }}</div>
        </div>
    </x-admin.personalization-page>
</x-layouts.admin>
