<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PersonalizationTemplate;
use App\Models\JerseyCustomizationOption;
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
            'masterColors' => $this->masterColors($personalizationTemplate),
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
        $masterColors = collect($this->masterColors($personalizationTemplate))->keyBy('hex');
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
                foreach ($decoded as $hex) {
                    $hex = strtoupper((string) $hex);
                    if (! preg_match('/^#[0-9A-F]{6}$/', $hex) || ! $masterColors->has($hex)) {
                        throw ValidationException::withMessages(['fields.'.$position.'.color_options' => 'Choose colors from the available Master Data options.']);
                    }
                    $chosenColors[$hex] = ['hex' => $hex, 'name' => $masterColors->get($hex)['name']];
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

    /** Colors already configured in Master Data for the template's product family. */
    private function masterColors(PersonalizationTemplate $template): array
    {
        $template->loadMissing('category.ancestors');
        $categoryNames = $template->category
            ? $template->category->ancestors->pluck('name')->push($template->category->name)->implode(' ')
            : '';
        $family = strtolower($categoryNames);
        // Master Data groups use singular product names, including jersey / shorts / headwear.
        $aliases = [
            'jersey' => ['jersey', 'jerseys'], 'shorts' => ['shorts'],
            'pants' => ['pants', 'trousers'], 'headwear' => ['cap', 'caps', 'hat', 'beanie', 'headwear'],
            'hoodie' => ['hoodie'], 'tshirt' => ['t-shirt', 'tshirt'], 'polo' => ['polo'],
            'jacket' => ['jacket'], 'bag' => ['bag', 'backpack'], 'uniform' => ['uniform'],
            'sweatshirt' => ['sweatshirt'], 'lanyard' => ['lanyard'],
        ];
        $groups = collect($aliases)->filter(fn ($names) => collect($names)->contains(fn ($name) => str_contains($family, $name)))->keys()->all();
        $colorTypes = collect(\App\Enums\JerseyCustomizationType::cases())
            ->filter(fn ($type) => str_contains($type->value, 'color') && in_array($type->group(), $groups, true))
            ->map(fn ($type) => $type->value)->values()->all();
        $master = $colorTypes
            ? JerseyCustomizationOption::query()->active()->whereIn('type', $colorTypes)
                ->whereNotNull('color_hex')->orderBy('name')->get(['name', 'color_hex'])
            : collect();
        $colors = $master->map(fn ($option) => [
            'hex' => '#'.strtoupper(ltrim((string) $option->color_hex, '#')),
            'name' => (string) $option->name,
        ]);
        // Pantone colors are the existing store-wide Master Data fallback.
        if ($colors->isEmpty()) {
            $colors = ArtworkPreference::query()->active()->where('type', ArtworkPreference::PANTONE)
                ->whereNotNull('hex_color')->ordered()->get(['name', 'hex_color'])
                ->map(fn ($option) => ['hex' => '#'.strtoupper(ltrim((string) $option->hex_color, '#')), 'name' => (string) $option->name]);
        }
        return $colors->filter(fn ($color) => preg_match('/^#[0-9A-F]{6}$/', $color['hex']))
            ->unique('hex')->take(100)->values()->all();
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
