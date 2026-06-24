<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parent_id', 'name', 'menu_label', 'slug', 'display_type', 'category_type', 'page_template', 'status',
    'depth', 'tree_path', 'eyebrow', 'short_title', 'description', 'short_description', 'description_html',
    'best_for', 'image_url', 'image_path', 'image_alt', 'thumbnail_path', 'thumbnail_url', 'thumbnail_alt',
    'banner_path', 'banner_url', 'banner_alt', 'mobile_banner_path', 'mobile_banner_url', 'mobile_banner_alt',
    'icon', 'cta_label', 'meta_title', 'meta_description', 'meta_keywords', 'canonical_url', 'og_title',
    'og_description', 'og_image_path', 'og_image_url', 'robots_index', 'robots_follow', 'schema_json',
    'match_rules', 'highlights', 'is_active', 'is_visible_in_catalog', 'is_visible_in_menu', 'is_featured',
    'show_product_count', 'include_descendant_products', 'default_product_sort', 'sort_order', 'published_at',
    'created_by', 'updated_by',
])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'match_rules' => 'array',
            'highlights' => 'array',
            'schema_json' => 'array',
            'is_active' => 'boolean',
            'is_visible_in_catalog' => 'boolean',
            'is_visible_in_menu' => 'boolean',
            'is_featured' => 'boolean',
            'show_product_count' => 'boolean',
            'include_descendant_products' => 'boolean',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'sort_order' => 'integer',
            'depth' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @param Builder<Category> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('status', 'active')
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /** @param Builder<Category> $query */
    public function scopeStorefrontVisible(Builder $query): Builder
    {
        return $query->active()->where('is_visible_in_catalog', true);
    }

    /**
     * A category is reachable only when it and every ancestor are publishable.
     * This prevents an active child from leaking through navigation, sitemaps, or
     * parent product aggregation when an intermediate category is hidden.
     *
     * @param Builder<Category> $query
     */
    public function scopeStorefrontReachable(Builder $query, bool $menuOnly = false): Builder
    {
        return $query
            ->storefrontVisible()
            ->when($menuOnly, fn (Builder $builder): Builder => $builder->where('is_visible_in_menu', true))
            ->whereNotExists(function ($blockedAncestors) use ($menuOnly): void {
                $blockedAncestors->selectRaw('1')
                    ->from('category_closure as reachability_cc')
                    ->join('categories as reachability_parent', 'reachability_parent.id', '=', 'reachability_cc.ancestor_id')
                    ->whereColumn('reachability_cc.descendant_id', 'categories.id')
                    ->where('reachability_cc.depth', '>', 0)
                    ->where(function ($blocked) use ($menuOnly): void {
                        $blocked->whereNotNull('reachability_parent.deleted_at')
                            ->orWhere('reachability_parent.is_active', false)
                            ->orWhere('reachability_parent.status', '!=', 'active')
                            ->orWhere('reachability_parent.is_visible_in_catalog', false)
                            ->when($menuOnly, fn ($builder) => $builder->orWhere('reachability_parent.is_visible_in_menu', false))
                            ->orWhere(function ($scheduled): void {
                                $scheduled->whereNotNull('reachability_parent.published_at')
                                    ->where('reachability_parent.published_at', '>', now());
                            });
                    });
            });
    }

    /** @param Builder<Category> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function ancestors(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'category_closure',
            'descendant_id',
            'ancestor_id'
        )->withPivot('depth')->wherePivot('depth', '>', 0)->orderByDesc('category_closure.depth');
    }

    public function descendants(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'category_closure',
            'ancestor_id',
            'descendant_id'
        )->withPivot('depth')->wherePivot('depth', '>', 0)->orderBy('category_closure.depth');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['is_primary', 'is_featured', 'sort_order'])
            ->withTimestamps();
    }

    /** Legacy relationship retained for backward compatibility. */
    public function legacyProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /** Legacy relationship retained for backward compatibility. */
    public function subcategoryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CategoryTag::class)->withTimestamps();
    }

    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(CatalogAttribute::class, 'category_filters', 'category_id', 'attribute_id')
            ->withPivot(['label', 'is_expanded', 'sort_order'])
            ->orderBy('category_filters.sort_order');
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(CategoryContentBlock::class)->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CategoryFaq::class)->orderBy('sort_order');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function thumbnailUrl(): string
    {
        return $this->mediaUrl($this->thumbnail_path, $this->thumbnail_url)
            ?: $this->mediaUrl($this->image_path, $this->image_url)
            ?: asset('images/category-placeholder.svg');
    }

    public function bannerUrl(bool $mobile = false): string
    {
        if ($mobile) {
            $mobileUrl = $this->mediaUrl($this->mobile_banner_path, $this->mobile_banner_url);
            if ($mobileUrl) {
                return $mobileUrl;
            }
        }

        return $this->mediaUrl($this->banner_path, $this->banner_url)
            ?: $this->thumbnailUrl();
    }

    public function ogImageUrl(): string
    {
        return $this->mediaUrl($this->og_image_path, $this->og_image_url)
            ?: $this->bannerUrl();
    }

    public function displayLabel(): string
    {
        return $this->menu_label ?: $this->name;
    }

    private function mediaUrl(?string $path, ?string $url): ?string
    {
        return PublicMedia::url($path, $url);
    }
}
