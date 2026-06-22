<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('menu_label', 160)->nullable()->after('name');
            $table->text('short_description')->nullable()->after('description');
            $table->longText('description_html')->nullable()->after('short_description');
            $table->string('category_type', 40)->default('standard')->after('display_type');
            $table->string('page_template', 50)->default('product_grid')->after('category_type');
            $table->string('status', 30)->default('active')->after('page_template');
            $table->unsignedSmallInteger('depth')->default(0)->after('parent_id');
            $table->text('tree_path')->nullable()->after('depth');

            $table->text('image_path')->nullable()->after('image_url');
            $table->text('thumbnail_path')->nullable()->after('image_alt');
            $table->text('thumbnail_url')->nullable()->after('thumbnail_path');
            $table->string('thumbnail_alt', 255)->nullable()->after('thumbnail_url');
            $table->text('banner_path')->nullable()->after('thumbnail_alt');
            $table->text('banner_url')->nullable()->after('banner_path');
            $table->string('banner_alt', 255)->nullable()->after('banner_url');
            $table->text('mobile_banner_path')->nullable()->after('banner_alt');
            $table->text('mobile_banner_url')->nullable()->after('mobile_banner_path');
            $table->string('mobile_banner_alt', 255)->nullable()->after('mobile_banner_url');
            $table->string('icon', 100)->nullable()->after('mobile_banner_alt');

            $table->boolean('is_visible_in_catalog')->default(true)->after('is_active');
            $table->boolean('is_visible_in_menu')->default(true)->after('is_visible_in_catalog');
            $table->boolean('is_featured')->default(false)->after('is_visible_in_menu');
            $table->boolean('show_product_count')->default(true)->after('is_featured');
            $table->boolean('include_descendant_products')->default(true)->after('show_product_count');
            $table->string('default_product_sort', 40)->default('featured')->after('include_descendant_products');
            $table->timestamp('published_at')->nullable()->after('sort_order');

            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->text('canonical_url')->nullable()->after('meta_keywords');
            $table->string('og_title', 255)->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->text('og_image_path')->nullable()->after('og_description');
            $table->text('og_image_url')->nullable()->after('og_image_path');
            $table->boolean('robots_index')->default(true)->after('og_image_url');
            $table->boolean('robots_follow')->default(true)->after('robots_index');
            $table->json('schema_json')->nullable()->after('robots_follow');

            $table->foreignId('created_by')->nullable()->after('schema_json')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index(['parent_id', 'status', 'is_visible_in_catalog', 'sort_order'], 'cat_parent_status_visible_sort_idx');
            $table->index(['is_featured', 'status', 'sort_order'], 'cat_featured_status_sort_idx');
            $table->index(['depth', 'sort_order'], 'cat_depth_sort_idx');
        });

        Schema::create('category_closure', function (Blueprint $table): void {
            $table->foreignId('ancestor_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedSmallInteger('depth');
            $table->primary(['ancestor_id', 'descendant_id'], 'category_closure_primary');
            $table->index(['descendant_id', 'depth'], 'cat_closure_desc_depth_idx');
            $table->index(['ancestor_id', 'depth'], 'cat_closure_anc_depth_idx');
        });

        Schema::create('category_product', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->primary(['category_id', 'product_id'], 'category_product_primary');
            $table->index(['product_id', 'is_primary'], 'cat_product_product_primary_idx');
            $table->index(['category_id', 'is_featured', 'sort_order'], 'cat_product_featured_sort_idx');
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('display_type', 30)->default('checkbox');
            $table->string('unit', 40)->nullable();
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'is_filterable', 'sort_order'], 'attr_active_filter_sort_idx');
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('label', 180);
            $table->string('slug', 180);
            $table->string('color_hex', 20)->nullable();
            $table->text('image_path')->nullable();
            $table->text('image_url')->nullable();
            $table->decimal('numeric_value', 14, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'slug'], 'attribute_value_slug_unique');
            $table->index(['attribute_id', 'is_active', 'sort_order'], 'attr_value_active_sort_idx');
        });

        Schema::create('attribute_value_product', function (Blueprint $table): void {
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['attribute_value_id', 'product_id'], 'attribute_value_product_primary');
            $table->index(['product_id', 'attribute_value_id'], 'attr_product_lookup_idx');
        });

        Schema::create('category_filters', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('label', 160)->nullable();
            $table->boolean('is_expanded')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['category_id', 'attribute_id'], 'category_filter_primary');
            $table->index(['category_id', 'sort_order'], 'category_filter_sort_idx');
        });

        Schema::create('category_content_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('block_type', 50)->default('rich_text');
            $table->string('heading', 255)->nullable();
            $table->string('subheading', 500)->nullable();
            $table->longText('content_html')->nullable();
            $table->text('image_path')->nullable();
            $table->text('image_url')->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('button_label', 160)->nullable();
            $table->text('button_url')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['category_id', 'is_active', 'sort_order'], 'cat_block_active_sort_idx');
        });

        Schema::create('category_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('question', 500);
            $table->longText('answer_html');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['category_id', 'is_active', 'sort_order'], 'cat_faq_active_sort_idx');
        });

        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('location', 80)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 180);
            $table->string('link_type', 30)->default('category');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('route_name', 180)->nullable();
            $table->text('url')->nullable();
            $table->string('target', 20)->default('_self');
            $table->string('css_class', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'is_active', 'sort_order'], 'menu_item_tree_idx');
        });

        Schema::create('url_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('old_path', 512)->unique();
            $table->text('new_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->nullableMorphs('redirectable');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'status_code'], 'redirect_active_status_idx');
        });

        $this->backfillCategoryTree();
        $this->backfillProductAssignments();
    }

    private function backfillCategoryTree(): void
    {
        $categories = DB::table('categories')->select(['id', 'parent_id', 'display_type', 'is_active'])->get()->keyBy('id');

        foreach ($categories as $category) {
            $depth = 0;
            $ancestorIds = [(int) $category->id];
            $currentParent = $category->parent_id ? (int) $category->parent_id : null;
            $guard = [];

            while ($currentParent && isset($categories[$currentParent]) && ! isset($guard[$currentParent])) {
                $guard[$currentParent] = true;
                $ancestorIds[] = $currentParent;
                $currentParent = $categories[$currentParent]->parent_id ? (int) $categories[$currentParent]->parent_id : null;
                $depth++;
            }

            DB::table('categories')->where('id', $category->id)->update([
                'depth' => $depth,
                'tree_path' => '/'.implode('/', array_reverse($ancestorIds)).'/',
                'category_type' => $category->display_type === 'sport' ? 'sport' : 'collection',
                'status' => $category->is_active ? 'active' : 'inactive',
            ]);

            DB::table('category_closure')->insert([
                'ancestor_id' => $category->id,
                'descendant_id' => $category->id,
                'depth' => 0,
            ]);

            foreach (array_slice($ancestorIds, 1) as $index => $ancestorId) {
                DB::table('category_closure')->insert([
                    'ancestor_id' => $ancestorId,
                    'descendant_id' => $category->id,
                    'depth' => $index + 1,
                ]);
            }
        }
    }

    private function backfillProductAssignments(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        DB::table('products')->select(['id', 'category_id', 'subcategory_id', 'sort_order'])->orderBy('id')->chunkById(250, function ($products): void {
            foreach ($products as $product) {
                $categoryIds = collect([$product->category_id, $product->subcategory_id])->filter()->unique()->values();
                $primaryId = $product->subcategory_id ?: $product->category_id;

                foreach ($categoryIds as $categoryId) {
                    DB::table('category_product')->updateOrInsert(
                        ['category_id' => $categoryId, 'product_id' => $product->id],
                        [
                            'is_primary' => (int) $categoryId === (int) $primaryId,
                            'is_featured' => false,
                            'sort_order' => $product->sort_order ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_redirects');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('category_faqs');
        Schema::dropIfExists('category_content_blocks');
        Schema::dropIfExists('category_filters');
        Schema::dropIfExists('attribute_value_product');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('category_closure');

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex('cat_parent_status_visible_sort_idx');
            $table->dropIndex('cat_featured_status_sort_idx');
            $table->dropIndex('cat_depth_sort_idx');
            $table->dropColumn([
                'menu_label', 'short_description', 'description_html', 'category_type', 'page_template', 'status',
                'depth', 'tree_path', 'image_path', 'thumbnail_path', 'thumbnail_url', 'thumbnail_alt',
                'banner_path', 'banner_url', 'banner_alt', 'mobile_banner_path', 'mobile_banner_url',
                'mobile_banner_alt', 'icon', 'is_visible_in_catalog', 'is_visible_in_menu', 'is_featured',
                'show_product_count', 'include_descendant_products', 'default_product_sort', 'published_at',
                'meta_keywords', 'canonical_url', 'og_title', 'og_description', 'og_image_path', 'og_image_url',
                'robots_index', 'robots_follow', 'schema_json', 'created_by', 'updated_by', 'deleted_at',
            ]);
        });
    }
};
