<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bulk_quote_payment_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 200)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
        $now = now();
        $names = [
            'Full payment (100% before production)',
            'Partial payment (deposit agreed separately)',
            '50% deposit / 50% before dispatch',
            '20% deposit / 80% before dispatch',
            '30% deposit / 70% before dispatch',
            'Payment in full before dispatch',
            'Net 30 days after invoice',
        ];
        foreach ($names as $position => $label) {
            DB::table('bulk_quote_payment_terms')->insert([
                'label' => $label, 'is_active' => true,
                'sort_order' => ($position + 1) * 10,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
    public function down(): void { Schema::dropIfExists('bulk_quote_payment_terms'); }
};
