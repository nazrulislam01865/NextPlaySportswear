<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\HomepageSection;
use App\Support\HomepageSectionRegistry;
use Tests\TestCase;

class ProductGalleryRevisionTest extends TestCase
{
    public function test_new_image_changes_gallery_revision_and_is_detectable_by_stale_form(): void
    {
        $first = new ProductImage();
        $first->forceFill(['id' => 10, 'path' => 'media-library/old.jpg', 'sort_order' => 0, 'is_primary' => true]);
        $second = new ProductImage();
        $second->forceFill(['id' => 11, 'path' => 'media-library/new.jpg', 'sort_order' => 1, 'is_primary' => false]);

        $product = new Product();
        $product->setRelation('images', collect([$first]));
        $oldRevision = $product->imageGalleryRevision();

        $product->setRelation('images', collect([$second, $first]));
        $newRevision = $product->imageGalleryRevision();

        $this->assertNotSame($oldRevision, $newRevision);
        $product->setRelation('images', collect([$first, $second]));
        $this->assertSame($newRevision, $product->imageGalleryRevision());
    }

    public function test_null_homepage_image_does_not_restore_definition_image(): void
    {
        $section = new HomepageSection();
        $section->forceFill(['key' => 'hero', 'image_path' => null, 'image_url' => null]);

        $merged = HomepageSectionRegistry::mergeForView('hero', $section);

        $this->assertNull($merged['image_path']);
        $this->assertNull($merged['image_url']);
    }
}
