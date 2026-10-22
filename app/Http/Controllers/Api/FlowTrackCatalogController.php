<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Server-only, allow-listed catalog interface. Never expose cost price or private customer columns. */
final class FlowTrackCatalogController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $input = $request->validate(['after' => ['sometimes', 'integer', 'min:0'], 'limit' => ['sometimes', 'integer', 'between:1,100']]);
        $limit = (int) ($input['limit'] ?? 100);
        $products = Product::query()->withTrashed()->with(['images', 'categories:id'])->where('id', '>', (int) ($input['after'] ?? 0))
            ->orderBy('id')->limit($limit)->get(['id', 'name', 'sku', 'slug', 'category_id', 'subcategory_id', 'status', 'is_active', 'base_price', 'currency', 'updated_at', 'deleted_at']);
        return response()->json(['data' => $products->map(fn (Product $p) => $this->summary($p)), 'next_after' => $products->count() === $limit ? $products->last()->id : null]);
    }

    /** Compact, cursor-paginated category tree for the existing FlowTracker catalog. */
    public function categories(Request $request): JsonResponse
    {
        $input = $request->validate(['after' => ['sometimes', 'integer', 'min:0'], 'limit' => ['sometimes', 'integer', 'between:1,100']]);
        $limit = (int) ($input['limit'] ?? 100);
        $rows = Category::query()->withTrashed()->where('id', '>', (int) ($input['after'] ?? 0))
            ->orderBy('id')->limit($limit)->get(['id', 'parent_id', 'name', 'slug', 'sort_order', 'is_active', 'status', 'deleted_at']);
        return response()->json(['data' => $rows->map(fn (Category $c) => $this->categorySummary($c)),
            'next_after' => $rows->count() === $limit ? $rows->last()->id : null]);
    }

    public function category(int $category): JsonResponse
    {
        $row = Category::query()->withTrashed()->findOrFail($category);
        return response()->json(['category' => $this->categorySummary($row)]);
    }

    private function categorySummary(Category $category): array
    {
        return [
            'id' => (int) $category->id,
            'parent_id' => $category->parent_id ? (int) $category->parent_id : null,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'sort_order' => (int) $category->sort_order,
            'active' => (bool) $category->is_active && $category->status === 'active' && $category->deleted_at === null,
        ];
    }

    public function customers(Request $request): JsonResponse
    {
        $input = $request->validate(['after' => ['sometimes', 'integer', 'min:0'], 'limit' => ['sometimes', 'integer', 'between:1,100']]);
        $limit = (int) ($input['limit'] ?? 100);
        $users = User::query()->where('role', 'customer')->where('is_active', true)
            ->where('id', '>', (int) ($input['after'] ?? 0))->orderBy('id')->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'company_name', 'updated_at']);
        return response()->json(['data' => $users->map(fn (User $u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'phone' => $u->phone, 'company_name' => $u->company_name,
            'version' => $this->customerVersion($u),
        ]), 'next_after' => $users->count() === $limit ? $users->last()->id : null]);
    }

    public function customer(int $customer): JsonResponse
    {
        $u = User::query()->where('role', 'customer')->findOrFail($customer);
        return response()->json(['customer' => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'phone' => $u->phone, 'company_name' => $u->company_name,
            'active' => (bool) $u->is_active,
            'version' => $this->customerVersion($u),
        ]]);
    }

    public function updateCustomer(Request $request, int $customer): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:80'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:60'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $response = DB::transaction(function () use ($customer, $data): array {
            $u = User::query()->where('role', 'customer')->lockForUpdate()->findOrFail($customer);
            if (! hash_equals($this->customerVersion($u), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Customer updated in NextPlay. Refresh before editing.']);
            }
            foreach (['name', 'phone', 'company_name'] as $key) {
                if (array_key_exists($key, $data)) $u->{$key} = $data[$key];
            }
            $u->save();
            return ['id' => $u->id, 'version' => $this->customerVersion($u)];
        });
        return response()->json(['customer' => $response]);
    }

    private function customerVersion(User $user): string
    {
        return hash('sha256', implode('|', [$user->updated_at?->format('Y-m-d H:i:s.u'), $user->name, $user->phone, $user->company_name, $user->email]));
    }

    public function product(int $product): JsonResponse
    {
        $p = Product::query()->withTrashed()->with([
            'category:id,name', 'categories:id,parent_id,name', 'images', 'optionGroups.values', 'sizeGroups.sizes',
            'priceTiers', 'fabricPriceTables.tiers', 'artworkMethods',
            'productionSpeeds', 'shippingMethods',
        ])->findOrFail($product);
        return response()->json(['product' => $this->summary($p), 'details' => [
            'currency' => (string) ($p->currency ?: 'USD'),
            'minimum_quantity' => (int) $p->minimum_quantity,
            'maximum_quantity' => $p->maximum_quantity,
            'is_customizable' => (bool) $p->is_customizable,
            'description' => (string) ($p->short_description ?? ''),
            'detail_description' => trim(strip_tags((string) ($p->description_html ?? ''))),
            'product_type' => (string) ($p->product_type ?? ''),
            'brand' => (string) ($p->brand ?? ''),
            'features' => $p->features ?? [],
            'tags' => $p->tags ?? [],
            'production_speeds' => $p->productionSpeeds->map(fn ($v) => ['name' => $v->name ?? $v->label ?? 'Production', 'days' => $v->days ?? null])->values(),
            'category_path' => $p->category?->name ?? '',
            'specifications' => $p->specifications ?? [],
            'image_urls' => $p->images->map(fn ($img) => ['id' => $img->id, 'url' => $img->publicUrl()])->values(),
            'option_groups' => $p->optionGroups->where('is_active', true)->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->name ?? $g->label ?? null,
                'required' => (bool) ($g->is_required ?? false),
                'type' => (string) ($g->type ?? 'select'),
                'minimum_selections' => (int) ($g->minimum_selections ?? (($g->is_required ?? false) ? 1 : 0)),
                'maximum_selections' => (int) ($g->maximum_selections ?? 1),
                'values' => $g->values->where('is_active', true)->map(fn ($v) => ['id' => $v->id, 'label' => $v->label ?? $v->name, 'extra_price' => $v->price_adjustment ?? 0])->values(),
            ])->values(),
            'size_groups' => $p->sizeGroups->where('is_active', true)->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->label ?? $g->name ?? '',
                'sizes' => $g->sizes->where('is_active', true)->map(fn ($s) => ['id' => $s->id, 'label' => $s->label ?? $s->name ?? '', 'code' => $s->code ?? ''])->values(),
            ])->values(),
            'price_tiers' => $p->priceTiers->map(fn ($t) => ['min' => (int) $t->minimum_quantity, 'max' => $t->maximum_quantity, 'unit' => (float) $t->unit_price])->values(),
            'fabric_price_tables' => $p->fabricPriceTables->map(fn ($t) => [
                'fabric_key' => $t->fabric_key, 'fabric_label' => $t->fabric_label, 'is_active' => (bool) $t->is_active,
                'tiers' => $t->tiers->map(fn ($tier) => ['min' => (int) $tier->minimum_quantity, 'max' => $tier->maximum_quantity, 'unit' => (float) $tier->unit_price])->values(),
            ])->values(),
            'artwork_upload_required' => (bool) $p->artwork_upload_required,
            'jersey_roster_enabled' => (bool) $p->jersey_roster_enabled,
            'jersey_roster_optional' => (bool) $p->jersey_roster_optional,
            'jersey_roster_fields' => $p->jersey_roster_fields ?? [],
        ]]);
    }

    public function update(Request $request, int $product): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:80'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $result = DB::transaction(function () use ($product, $data): array {
            $p = Product::query()->lockForUpdate()->findOrFail($product);
            if ($p->deleted_at) abort(404);
            if (! hash_equals($this->version($p), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Product changed in NextPlay. Refresh before saving.']);
            }
            if (array_key_exists('name', $data)) $p->name = $data['name'];
            if (array_key_exists('is_active', $data)) {
                $p->is_active = (bool) $data['is_active'];
                $p->status = $p->is_active ? 'active' : 'inactive';
            }
            $p->save();
            return $this->summary($p);
        });
        return response()->json(['product' => $result]);
    }

    private function version(Product $product): string
    {
        return hash('sha256', implode('|', [$product->updated_at?->format('Y-m-d H:i:s.u'), $product->name, (int) $product->is_active, $product->status]));
    }

    private function summary(Product $p): array
    {
        return [
            'id' => $p->id, 'sku' => $p->sku, 'name' => $p->name,
            'slug' => $p->slug, 'category_id' => $p->category_id,
            'subcategory_id' => $p->subcategory_id,
            'primary_category_id' => $p->relationLoaded('categories') ? $p->categories->first(fn ($c) => (bool) ($c->pivot?->is_primary ?? false))?->id : null,
            'category_ids' => $p->relationLoaded('categories') ? $p->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all() : [],
            'active' => $p->status === 'active' && (bool) $p->is_active && $p->deleted_at === null,
            'currency' => $p->currency ?: 'USD', 'base_price' => (float) $p->base_price,
            'thumbnail_url' => $p->primaryImageUrl(),
            'version' => $this->version($p),
        ];
    }
}
