<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rural_area_surcharges', function (Blueprint $table): void {
            if (! Schema::hasColumn('rural_area_surcharges', 'carrier')) {
                $table->string('carrier', 40)->default('UPS')->after('name');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'iata_code')) {
                $table->string('iata_code', 3)->nullable()->after('country');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_low')) {
                $table->string('postal_code_low', 32)->nullable()->after('state');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_high')) {
                $table->string('postal_code_high', 32)->nullable()->after('postal_code_low');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_low_normalized')) {
                $table->string('postal_code_low_normalized', 32)->nullable()->after('postal_code_high');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_high_normalized')) {
                $table->string('postal_code_high_normalized', 32)->nullable()->after('postal_code_low_normalized');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_low_numeric')) {
                $table->unsignedBigInteger('postal_code_low_numeric')->nullable()->after('postal_code_high_normalized');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'postal_code_high_numeric')) {
                $table->unsignedBigInteger('postal_code_high_numeric')->nullable()->after('postal_code_low_numeric');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'city')) {
                $table->string('city', 160)->nullable()->after('postal_code_patterns');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'city_normalized')) {
                $table->string('city_normalized', 160)->nullable()->after('city');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'origin_surcharge')) {
                $table->string('origin_surcharge', 80)->nullable()->after('city_normalized');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'destination_surcharge')) {
                $table->string('destination_surcharge', 80)->nullable()->after('origin_surcharge');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'extra_charge')) {
                $table->decimal('extra_charge', 12, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'source_file')) {
                $table->string('source_file', 255)->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'source_row')) {
                $table->unsignedInteger('source_row')->nullable()->after('source_file');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'source_hash')) {
                $table->char('source_hash', 64)->nullable()->after('source_row');
            }
            if (! Schema::hasColumn('rural_area_surcharges', 'import_batch_id')) {
                $table->uuid('import_batch_id')->nullable()->after('source_hash');
            }
        });

        // Preserve the amount of every pre-existing manual rule. Checkout now reads
        // extra_charge for structured records, while amount remains for backwards compatibility.
        DB::table('rural_area_surcharges')->update([
            'extra_charge' => DB::raw('amount'),
        ]);

        Schema::table('rural_area_surcharges', function (Blueprint $table): void {
            $table->index(['carrier', 'iata_code', 'is_active'], 'ras_carrier_iata_active_idx');
            $table->index(['carrier', 'iata_code', 'postal_code_low_numeric'], 'ras_numeric_lookup_idx');
            $table->index(['carrier', 'iata_code', 'city_normalized'], 'ras_city_lookup_idx');
            $table->index(['destination_surcharge', 'is_active'], 'ras_destination_active_idx');
            $table->unique('source_hash', 'ras_source_hash_unique');
            $table->index('import_batch_id', 'ras_import_batch_idx');
        });

        Schema::create('rural_area_surcharge_import_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('carrier', 40)->default('UPS');
            $table->string('source_file', 255);
            $table->decimal('extra_charge', 12, 2)->default(0);
            $table->boolean('replace_existing')->default(true);
            $table->unsignedInteger('expected_rows')->default(0);
            $table->unsignedInteger('rows_received')->default(0);
            $table->string('status', 24)->default('uploading');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['created_by', 'status'], 'ras_import_owner_status_idx');
            $table->index('created_at', 'ras_import_created_idx');
        });

        Schema::create('rural_area_surcharge_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('source_row');
            $table->string('country', 120);
            $table->string('iata_code', 3);
            $table->string('postal_code_low', 32);
            $table->string('postal_code_high', 32);
            $table->string('postal_code_low_normalized', 32);
            $table->string('postal_code_high_normalized', 32);
            $table->unsignedBigInteger('postal_code_low_numeric')->nullable();
            $table->unsignedBigInteger('postal_code_high_numeric')->nullable();
            $table->string('city', 160)->nullable();
            $table->string('city_normalized', 160)->nullable();
            $table->string('origin_surcharge', 80);
            $table->string('destination_surcharge', 80);
            $table->char('source_hash', 64);
            $table->timestamps();

            $table->foreign('batch_id', 'ras_import_rows_batch_fk')
                ->references('id')
                ->on('rural_area_surcharge_import_batches')
                ->cascadeOnDelete();
            $table->unique(['batch_id', 'source_hash'], 'ras_import_rows_batch_hash_unique');
            $table->index(['batch_id', 'id'], 'ras_import_rows_batch_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rural_area_surcharge_import_rows');
        Schema::dropIfExists('rural_area_surcharge_import_batches');

        Schema::table('rural_area_surcharges', function (Blueprint $table): void {
            $table->dropUnique('ras_source_hash_unique');
            $table->dropIndex('ras_carrier_iata_active_idx');
            $table->dropIndex('ras_numeric_lookup_idx');
            $table->dropIndex('ras_city_lookup_idx');
            $table->dropIndex('ras_destination_active_idx');
            $table->dropIndex('ras_import_batch_idx');

            $table->dropColumn([
                'carrier',
                'iata_code',
                'postal_code_low',
                'postal_code_high',
                'postal_code_low_normalized',
                'postal_code_high_normalized',
                'postal_code_low_numeric',
                'postal_code_high_numeric',
                'city',
                'city_normalized',
                'origin_surcharge',
                'destination_surcharge',
                'extra_charge',
                'source_file',
                'source_row',
                'source_hash',
                'import_batch_id',
            ]);
        });
    }
};
