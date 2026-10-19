<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkPreference;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArtworkPreferenceController extends Controller
{
    public function __construct(private readonly ProductCatalogCacheService $catalogCache)
    {
    }

    public function index(string $type): View
    {
        $this->assertType($type);

        $searchQuery = request()->query('search', '');
        $search = is_string($searchQuery) ? trim(substr($searchQuery, 0, 100)) : '';
        $status = request()->query('status', '');

        $preferences = ArtworkPreference::query()->where('type', $type)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    $matches->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.artwork-preferences.index', [
            'type' => $type,
            'heading' => $this->heading($type),
            'preferences' => $preferences,
            'search' => $search,
            'statusFilter' => $status,
        ]);
    }

    public function create(string $type): View
    {
        $this->assertType($type);

        return view('admin.artwork-preferences.create', [
            'type' => $type,
            'heading' => $this->heading($type),
            'preference' => new ArtworkPreference(['type' => $type, 'is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function edit(string $type, ArtworkPreference $preference): View
    {
        $this->assertType($type);
        abort_unless($preference->type === $type, 404);

        return view('admin.artwork-preferences.edit', [
            'type' => $type,
            'heading' => $this->heading($type),
            'preference' => $preference,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->assertType($type);
        ArtworkPreference::query()->create(['type' => $type] + $this->validated($request, $type));
        $this->catalogCache->flush();

        return redirect()->route('admin.artwork-preferences.index', $type)->with('status', 'Artwork preference created.');
    }

    public function update(Request $request, string $type, ArtworkPreference $preference): RedirectResponse
    {
        $this->assertType($type);
        abort_unless($preference->type === $type, 404);
        $preference->update($this->validated($request, $type, $preference));
        $this->catalogCache->flush();

        return redirect()->route('admin.artwork-preferences.index', $type)->with('status', 'Artwork preference updated.');
    }

    public function destroy(string $type, ArtworkPreference $preference): RedirectResponse
    {
        $this->assertType($type);
        abort_unless($preference->type === $type, 404);
        $preference->delete(); // Pivot is removed by the FK; existing order snapshots remain unchanged.
        $this->catalogCache->flush();

        return redirect()->route('admin.artwork-preferences.index', $type)->with('status', 'Artwork preference deleted.');
    }

    private function heading(string $type): string
    {
        return $type === ArtworkPreference::PANTONE ? 'Pantone Colors' : 'Design Styles';
    }

    private function assertType(string $type): void
    {
        abort_unless(in_array($type, [ArtworkPreference::PANTONE, ArtworkPreference::STYLE], true), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $type, ?ArtworkPreference $preference = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9 .-]*$/',
                Rule::unique('artwork_preferences', 'code')->where('type', $type)->ignore($preference?->id),
            ],
            'hex_color' => [$type === ArtworkPreference::PANTONE ? 'required' : 'nullable', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = trim($data['code']);
        $data['name'] = trim($data['name']);
        $data['hex_color'] = $type === ArtworkPreference::PANTONE ? strtoupper($data['hex_color']) : null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
