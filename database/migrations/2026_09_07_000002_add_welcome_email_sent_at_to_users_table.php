<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('welcome_email_sent_at')
                ->nullable()
                ->after('email_verified_at');
        });

        // Before this migration the application sent the welcome message at
        // registration time. Mark existing customer accounts as already
        // welcomed so deploying this change does not send them a duplicate
        // welcome email when they next verify or re-verify an address.
        DB::table('users')
            ->where('role', 'customer')
            ->whereNull('welcome_email_sent_at')
            ->update([
                'welcome_email_sent_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('welcome_email_sent_at');
        });
    }
};
