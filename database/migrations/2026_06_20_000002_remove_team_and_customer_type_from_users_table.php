<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'customer_type')) {
                $table->dropColumn('customer_type');
            }

            if (Schema::hasColumn('users', 'team_name')) {
                $table->dropColumn('team_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'team_name')) {
                $table->string('team_name', 150)->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('users', 'customer_type')) {
                $table->string('customer_type', 40)->nullable()->after('team_name');
            }
        });
    }
};
