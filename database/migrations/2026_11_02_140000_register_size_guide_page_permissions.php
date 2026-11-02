<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installations already ran the shared storefront-page migration.
        // Register only missing permission rows while retaining custom role grants.
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        // Do not remove permissions assigned to existing admin roles on rollback.
    }
};
