<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PersonalizationTemplate;
use App\Models\ArtworkPreference;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonalizationTemplateController extends Controller
{
    public function __construct(private readonly ProductCatalogCacheService $catalogCache)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'draft'])],
        ]);
        $query = PersonalizationTemplate::query()->with('category.ancestors')
            ->withCount('products')->latest('id');
        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$escaped.'%')
                ->orWhere('section_title', 'like', '%'.$escaped.'%')
                ->orWhere('fields', 'like', '%'.$escaped.'%'));
        }
        if (! empty($filters['category'])) {
            $query->where('category_id', (int) $filters['category']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'draft') {
                $query->where('is_published', false);
            } else {
                $query->where('is_published', true)->where('is_active', $filters['status'] === 'active');
            }
        }

        return view('admin.personalization-templates.index', [
            'templates' => $query->paginate(15)->withQueryString(),
            'categories' => $this->categories(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return $this->basicView(new PersonalizationTemplate([
            'section_title' => 'Custom Text & Number',
            'description' => 'Add custom text and a number to your item.',
            'shared_entry_label' => 'Use the same text & number for all items',
            'shared_entry_enabled' => true,
            'is_active' => true,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBasic($request);
        $template = PersonalizationTemplate::query()->create($data + ['fields' => [], 'is_published' => false]);
        if ($request->input('action') === 'draft') {
            return redirect()->route('admin.personalization-templates.index')->with('status', 'Template draft saved.');
        }
        return redirect()->route('admin.personalization-templates.fields', $template);
    }

    public function edit(PersonalizationTemplate $personalizationTemplate): View
    {
        return $this->basicView($personalizationTemplate);
    }

    public function update(Request $request, PersonalizationTemplate $personalizationTemplate): RedirectResponse
    {
        $data = $this->validateBasic($request, $personalizationTemplate);
        $personalizationTemplate->update($data);
        $this->flushIfAssigned($personalizationTemplate);
        if ($request->input('action') === 'draft') {
            return redirect()->route('admin.personalization-templates.index')->with('status', 'Template saved.');
        }
        return redirect()->route('admin.personalization-templates.fields', $personalizationTemplate);
    }

    public function fields(PersonalizationTemplate $personalizationTemplate): View
    {
        return view('admin.personalization-templates.fields', [
            'template' => $personalizationTemplate->load('category.ancestors'),
            'existingPantones' => $this->existingPantones($personalizationTemplate),
        ]);
    }

    /** Search only active Pantone master records; never send the full catalog to the browser. */
    public function searchPantones(Request $request, PersonalizationTemplate $personalizationTemplate): \Illuminate\Http\JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
        $term = trim((string) ($filters['q'] ?? ''));
        $query = ArtworkPreference::query()->where('type', ArtworkPreference::PANTONE)
            ->active()->whereNotNull('hex_color');
        if ($term !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $query->where(function ($matching) use ($escaped) {
                $matching->where('name', 'like', '%'.$escaped.'%')
                    ->orWhere('code', 'like', '%'.$escaped.'%')
                    ->orWhere('hex_color', 'like', '%'.$escaped.'%');
            });
        }
        $page = $query->ordered()->simplePaginate(30, ['id', 'name', 'code', 'hex_color'], 'page', (int) ($filters['page'] ?? 1));

        return response()->json([
            'colors' => $page->getCollection()->map(fn (ArtworkPreference $color) => $this->pantoneData($color))->values(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    public function saveFields(Request $request, PersonalizationTemplate $personalizationTemplate): RedirectResponse
    {
        $data = $request->validate([
            'fields' => ['required', 'array', 'min:1', 'max:20'],
            'fields.*.key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'number', 'color', 'image', 'file'])],
            'fields.*.placeholder' => ['nullable', 'string', 'max:160'],
            'fields.*.max_length' => ['nullable', 'integer', 'min:0', 'max:250'],
            'fields.*.color_options' => ['nullable', 'string', 'max:15000'],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.enabled' => ['required', 'boolean'],
        ]);
        $requestedIds = collect($data['fields'])->filter(fn ($field) => $field['type'] === 'color')
            ->flatMap(function ($field) {
                $ids = json_decode((string) ($field['color_options'] ?? '[]'), true);
                return is_array($ids) ? $ids : [];
            })->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)->unique()->values()->all();
        $masterColors = ArtworkPreference::query()->where('type', ArtworkPreference::PANTONE)
            ->active()->whereIn('id', $requestedIds)->get()->keyBy('id');
        $used = [];
        $fields = [];
        foreach ($data['fields'] as $position => $field) {
            if (in_array($field['type'], ['text', 'number'], true) && (int) ($field['max_length'] ?? 0) < 1) {
                throw ValidationException::withMessages(['fields.'.$position.'.max_length' => 'Enter a maximum character count between 1 and 250.']);
            }
            $key = (string) (($field['key'] ?? '') ?: Str::slug($field['label'], '_'));
            if ($key === '' || isset($used[$key])) {
                throw ValidationException::withMessages(['fields' => 'Each field needs a distinct name and key. Please check field '.($position + 1).'.']);
            }
            $used[$key] = true;
            $chosenColors = [];
            if ($field['type'] === 'color') {
                $decoded = json_decode((string) ($field['color_options'] ?? '[]'), true);
                if (! is_array($decoded) || count($decoded) > 100) {
                    throw ValidationException::withMessages(['fields.'.$position.'.color_options' => 'Choose valid colors from Master Data.']);
                }
                foreach ($decoded as $id) {
                    if (! is_scalar($id) || ! ctype_digit((string) $id)
                        || (int) $id < 1 || ! $masterColors->has((int) $id)) {
                        throw ValidationException::withMessages(['fields.'.$position.'.color_options' => 'Choose active Pantone colors from Master Data.']);
                    }
                    $color = $masterColors->get((int) $id);
                    if (! preg_match('/^#[0-9A-F]{6}$/', strtoupper((string) $color->hex_color))) {
                        throw ValidationException::withMessages(['fields.'.$position.'.color_options' => 'Choose a Pantone color with a valid HEX value.']);
                    }
                    $chosenColors[$color->id] = $this->pantoneData($color);
                }
                if (! count($chosenColors) && (bool) $field['enabled']) {
                    throw ValidationException::withMessages(['fields.'.$position.'.color_options' => 'Select at least one available color.']);
                }
            }
            $fields[] = [
                'key' => $key, 'label' => trim($field['label']), 'type' => $field['type'],
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'max_length' => in_array($field['type'], ['text', 'number'], true) ? (int) ($field['max_length'] ?? 60) : 0,
                'required' => (bool) $field['required'], 'enabled' => (bool) $field['enabled'],
                'color_options' => array_values($chosenColors),
            ];
        }
        if (! collect($fields)->contains(fn ($field) => $field['enabled'])) {
            throw ValidationException::withMessages(['fields' => 'Enable at least one field before continuing.']);
        }
        $personalizationTemplate->update(['fields' => $fields]);
        $this->flushIfAssigned($personalizationTemplate);
        return redirect()->route('admin.personalization-templates.preview', $personalizationTemplate);
    }

    public function preview(PersonalizationTemplate $personalizationTemplate): View|RedirectResponse
    {
        if (collect($personalizationTemplate->fields)->isEmpty()) {
            return redirect()->route('admin.personalization-templates.fields', $personalizationTemplate)
                ->with('status', 'Add the customer fields before previewing.');
        }
        return view('admin.personalization-templates.preview', [
            'template' => $personalizationTemplate->load('category'),
        ]);
    }

    public function publish(PersonalizationTemplate $personalizationTemplate): RedirectResponse
    {
        if (! collect($personalizationTemplate->fields)->contains(fn ($field) => ($field['enabled'] ?? true))) {
            throw ValidationException::withMessages(['fields' => 'Configure at least one enabled field before publishing.']);
        }
        $personalizationTemplate->update(['is_published' => true]);
        $this->flushIfAssigned($personalizationTemplate);
        return redirect()->route('admin.personalization-templates.index')->with('status', 'Personalization template saved. Assign it in the product options.');
    }

    public function duplicate(PersonalizationTemplate $personalizationTemplate): RedirectResponse
    {
        $clone = $personalizationTemplate->replicate();
        $clone->name = Str::limit($clone->name.' (Copy)', 160, '');
        $clone->is_published = false;
        $clone->save();
        return redirect()->route('admin.personalization-templates.edit', $clone)->with('status', 'Template duplicated as a draft.');
    }

    public function destroy(PersonalizationTemplate $personalizationTemplate): RedirectResponse
    {
        if ($personalizationTemplate->products()->exists()) {
            return back()->withErrors(['template' => 'This template is assigned to products. Unassign it from those products before deleting.']);
        }
        $personalizationTemplate->delete();
        return redirect()->route('admin.personalization-templates.index')->with('status', 'Template deleted.');
    }

    /** Resolve only previously chosen colors so editing retains existing choices. */
    private function existingPantones(PersonalizationTemplate $template): array
    {
        $existing = old('fields', $template->fields ?: []);
        if (! is_array($existing)) return [];
        $ids = [];
        $hexes = [];
        foreach ($existing as $field) {
            if (! is_array($field)) continue;
            $choices = $field['color_options'] ?? [];
            if (is_string($choices)) $choices = json_decode($choices, true);
            if (! is_array($choices)) continue;
            foreach ($choices as $choice) {
                if (is_array($choice)) {
                    if (! empty($choice['id'])) $ids[] = (int) $choice['id'];
                    if (! empty($choice['hex'])) $hexes[] = strtoupper((string) $choice['hex']);
                } elseif (is_scalar($choice) && ctype_digit((string) $choice)) {
                    $ids[] = (int) $choice;
                } elseif (is_string($choice) && preg_match('/^#[0-9a-fA-F]{6}$/', $choice)) {
                    $hexes[] = strtoupper($choice);
                }
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        $hexes = array_values(array_unique($hexes));
        if (! $ids && ! $hexes) return [];

        return ArtworkPreference::query()->where('type', ArtworkPreference::PANTONE)->active()
            ->where(function ($query) use ($ids, $hexes) {
                if ($ids) $query->whereIn('id', $ids);
                if ($hexes) $query->{$ids ? 'orWhereIn' : 'whereIn'}('hex_color', $hexes);
            })->get(['id', 'name', 'code', 'hex_color'])->map(fn (ArtworkPreference $color) => $this->pantoneData($color))->all();
    }

    private function pantoneData(ArtworkPreference $color): array
    {
        return [
            'id' => $color->id,
            'name' => $color->name,
            'code' => $color->code,
            'hex' => strtoupper((string) $color->hex_color),
        ];
    }

    private function basicView(PersonalizationTemplate $template): View
    {
        $configuredCategoryIds = PersonalizationTemplate::query()->distinct()->pluck('category_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true]);

        // Do not offer already configured categories for another new setup.
        // Preserve the existing category when editing, including old duplicates.
        $categories = $this->categories()->filter(fn (Category $category) =>
            ! $configuredCategoryIds->has($category->id)
            || ($template->exists && (int) $template->category_id === $category->id)
        )->values();

        return view('admin.personalization-templates.basic', [
            'template' => $template, 'categories' => $categories,
        ]);
    }

    private function categories()
    {
        // One eager-loaded ancestor lookup, never one query per displayed category.
        return Category::query()->leaf()->with('ancestors:id,name')->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->each(fn (Category $category) => $category->setAttribute('display_path',
                $category->ancestors->pluck('name')->push($category->name)->implode(' › ')))
            ->sortBy('display_path', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function flushIfAssigned(PersonalizationTemplate $template): void
    {
        if ($template->products()->exists()) {
            $this->catalogCache->flush();
        }
    }

    private function validateBasic(Request $request, ?PersonalizationTemplate $template = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:160'],
            'section_title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'shared_entry_label' => ['nullable', 'string', 'max:180'],
            'shared_entry_enabled' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
        $category = Category::query()->findOrFail((int) $data['category_id']);
        if (! $category->isLeaf()) {
            throw ValidationException::withMessages(['category_id' => 'Select a last-level product category.']);
        }
        if ((! $template?->exists || (int) $template->category_id !== $category->id)
            && PersonalizationTemplate::query()->where('category_id', $category->id)->exists()) {
            throw ValidationException::withMessages([
                'category_id' => 'Personalization has already been added for this category. Edit the existing setup instead.',
            ]);
        }
        if ($template?->exists && ! $data['is_active'] && $template->is_active && $template->products()->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Unassign this template from its products before deactivating it.']);
        }
        if ($template?->exists && (int) $template->category_id !== $category->id && $template->products()->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Unassign this template from its products before changing the category.']);
        }
        return $data;
    }
}
