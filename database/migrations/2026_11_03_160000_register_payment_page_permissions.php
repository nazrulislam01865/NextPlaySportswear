<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installations already have the storefront_page_settings table.
        // Register only the new editor permissions, preserving existing grants.
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        // Keep permission grants intact if this migration is rolled back.
    }
};
