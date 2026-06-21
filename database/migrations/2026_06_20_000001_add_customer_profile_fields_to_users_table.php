<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name', 150)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'team_name')) {
                $table->string('team_name', 150)->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('users', 'customer_type')) {
                $table->string('customer_type', 40)->nullable()->after('team_name');
            }

            if (! Schema::hasColumn('users', 'preferred_sport')) {
                $table->string('preferred_sport', 60)->nullable()->after('customer_type');
            }

            if (! Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('preferred_sport');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'marketing_consent',
                'preferred_sport',
                'customer_type',
                'team_name',
                'company_name',
                'phone',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
