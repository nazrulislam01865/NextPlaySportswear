<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('flowtrack_operational_status', 60)->nullable();
            $table->string('flowtrack_operational_stage', 120)->nullable();
            $table->unsignedTinyInteger('flowtrack_operational_progress')->nullable();
            $table->timestamp('flowtrack_operational_updated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn([
            'flowtrack_operational_status', 'flowtrack_operational_stage',
            'flowtrack_operational_progress', 'flowtrack_operational_updated_at',
        ]));
    }
};
