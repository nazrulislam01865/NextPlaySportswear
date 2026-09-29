<?php

use App\Models\SaleCampaign;
use App\Services\Promotions\SaleCampaignCodeGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_campaigns') || ! Schema::hasColumn('sale_campaigns', 'internal_code')) {
            return;
        }

        $generator = app(SaleCampaignCodeGenerator::class);

        SaleCampaign::withTrashed()
            ->where(function ($query): void {
                $query->whereNull('internal_code')->orWhere('internal_code', '');
            })
            ->orderBy('id')
            ->each(function (SaleCampaign $campaign) use ($generator): void {
                $campaign->forceFill([
                    'internal_code' => $generator->generate((string) $campaign->name),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        // Generated internal identifiers are intentionally stable and are not removed on rollback.
    }
};
