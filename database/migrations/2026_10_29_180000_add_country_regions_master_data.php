<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_calling_codes', function (Blueprint $table): void {
            $table->string('region_label', 30)->default('State / Province');
        });

        foreach (['US' => 'State', 'CA' => 'Province', 'GB' => 'County', 'AU' => 'State / Territory', 'BD' => 'Division', 'AE' => 'Emirate', 'IN' => 'State / Territory', 'NZ' => 'Region'] as $iso => $label) {
            DB::table('country_calling_codes')->where('iso_code', $iso)->update(['region_label' => $label]);
        }

        Schema::create('country_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_calling_code_id')->constrained('country_calling_codes')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['country_calling_code_id', 'name'], 'country_regions_country_name_unique');
            $table->index(['country_calling_code_id', 'is_active', 'sort_order'], 'country_regions_public_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_regions');
        Schema::table('country_calling_codes', function (Blueprint $table): void {
            $table->dropColumn('region_label');
        });
    }
};
