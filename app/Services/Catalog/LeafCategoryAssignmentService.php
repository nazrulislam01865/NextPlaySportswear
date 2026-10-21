<?php

namespace App\Services\Catalog;

class LeafCategoryAssignmentService
{
    /**
     * Category reparenting, imports and ordinary metadata edits must never
     * silently delete product assignments. A category that gains children can
     * retain historical direct assignments; storefront category queries already
     * support those alongside descendant leaf assignments.
     *
     * New assignments are still restricted to leaf categories by the product
     * request validation and the category assignment controller. Removing an
     * existing assignment remains an explicit admin action.
     */
    public function enforce(): int
    {
        return 0;
    }
}
