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
        // Keep permissions in place. Removing them can break existing role assignments.
    }
};
