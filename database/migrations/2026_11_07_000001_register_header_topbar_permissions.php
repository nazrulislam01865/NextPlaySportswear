<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Register new permissions without overwriting existing role customization.
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        // Keep existing role grants when rolling back this feature.
    }
};
