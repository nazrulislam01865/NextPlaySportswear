<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        // Permission rows are centrally managed by AdminRbac and intentionally
        // left in place on rollback to avoid removing assignments unexpectedly.
    }
};
