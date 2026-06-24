<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('size_option_groups') && ! Schema::hasColumn('size_option_groups', 'chart_html')) {
            Schema::table('size_option_groups', function (Blueprint $table): void {
                $table->longText('chart_html')->nullable()->after('description_html');
            });
        }

        if (Schema::hasTable('product_size_groups') && ! Schema::hasColumn('product_size_groups', 'chart_html')) {
            Schema::table('product_size_groups', function (Blueprint $table): void {
                $table->longText('chart_html')->nullable()->after('description_html');
            });
        }

        $this->migrateLegacyChartData('size_option_groups');
        $this->migrateLegacyChartData('product_size_groups');
    }

    public function down(): void
    {
        if (Schema::hasTable('product_size_groups') && Schema::hasColumn('product_size_groups', 'chart_html')) {
            Schema::table('product_size_groups', function (Blueprint $table): void {
                $table->dropColumn('chart_html');
            });
        }

        if (Schema::hasTable('size_option_groups') && Schema::hasColumn('size_option_groups', 'chart_html')) {
            Schema::table('size_option_groups', function (Blueprint $table): void {
                $table->dropColumn('chart_html');
            });
        }
    }

    private function migrateLegacyChartData(string $table): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'chart_html')
            || ! Schema::hasColumn($table, 'chart_columns')
            || ! Schema::hasColumn($table, 'chart_rows')) {
            return;
        }

        DB::table($table)
            ->whereNull('chart_html')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table): void {
                foreach ($records as $record) {
                    $hasImage = trim((string) ($record->chart_image_path ?? '')) !== ''
                        || trim((string) ($record->chart_image_url ?? '')) !== '';
                    if ($hasImage) {
                        continue;
                    }

                    $columns = $this->decodeArray($record->chart_columns ?? null);
                    $rows = $this->decodeArray($record->chart_rows ?? null);
                    $title = trim((string) ($record->chart_title ?? ''));
                    $note = trim((string) ($record->chart_note ?? ''));

                    if ($columns === [] || $rows === []) {
                        continue;
                    }

                    $html = '';
                    if ($title !== '') {
                        $html .= '<h3>'.$this->escape($title).'</h3>';
                    }
                    if ($note !== '') {
                        $html .= '<p>'.$this->escape($note).'</p>';
                    }
                    $html .= '<table><thead><tr>';
                    foreach ($columns as $column) {
                        $html .= '<th>'.$this->escape((string) $column).'</th>';
                    }
                    $html .= '</tr></thead><tbody>';
                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $html .= '<tr>';
                        foreach ($row as $cell) {
                            $html .= '<td>'.$this->escape((string) $cell).'</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table>';

                    DB::table($table)->where('id', $record->id)->update(['chart_html' => $html]);
                }
            });
    }

    /** @return array<int, mixed> */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
};
