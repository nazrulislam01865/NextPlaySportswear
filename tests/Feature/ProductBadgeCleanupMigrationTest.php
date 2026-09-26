<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBadgeCleanupMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_removes_existing_customizable_badges_without_touching_other_badges(): void
    {
        $customizable = Product::query()->create([
            'name' => 'Legacy Customizable Product',
            'slug' => 'legacy-customizable-product',
            'sku' => 'LEGACY-CUSTOMIZABLE',
            'status' => 'active',
            'badge_label' => ' Customizable ',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
        ]);

        $legacyInitialBadge = Product::query()->create([
            'name' => 'Legacy C Badge Product',
            'slug' => 'legacy-c-badge-product',
            'sku' => 'LEGACY-C-BADGE',
            'status' => 'active',
            'badge_label' => ' C ',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
        ]);

        $otherBadge = Product::query()->create([
            'name' => 'Limited Product',
            'slug' => 'limited-product',
            'sku' => 'LIMITED-PRODUCT',
            'status' => 'active',
            'badge_label' => 'Limited Edition',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
        ]);

        $migration = require database_path('migrations/2026_09_26_000002_remove_legacy_customizable_product_badges.php');
        $migration->up();

        $this->assertNull($customizable->fresh()->badge_label);
        $this->assertNull($legacyInitialBadge->fresh()->badge_label);
        $this->assertSame('Limited Edition', $otherBadge->fresh()->badge_label);
    }
}
