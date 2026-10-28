<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonalizationCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        // One row per configured category, even when legacy data contains
        // several templates for that category. No product-list scans or N+1s.
        $query = Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->whereHas('personalizationTemplates')
            ->with([
                'ancestors:id,name',
                'personalizationTemplates' => fn ($builder) => $builder
                    ->withCount('products')
                    ->orderBy('id'),
            ]);

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', $pattern)
                ->orWhereHas('personalizationTemplates', fn ($templates) => $templates
                    ->where('name', 'like', $pattern)
                    ->orWhere('section_title', 'like', $pattern)
                    ->orWhere('fields', 'like', $pattern)));
        }

        return view('admin.personalization-templates.categories', [
            'categories' => $query->orderBy('name')->orderBy('id')
                ->paginate($this->adminPerPage(15))->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
