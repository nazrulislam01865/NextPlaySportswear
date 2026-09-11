<?php

namespace App\Services\Shipping;

use App\Models\RuralAreaSurchargeImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RemoteAreaSurchargeImportService
{
    private const CHUNK_SIZE = 1000;

    public function __construct(
        private readonly RemoteAreaSurchargeNormalizer $normalizer,
    ) {
    }

    public function start(int $adminId, array $payload): RuralAreaSurchargeImportBatch
    {
        // Remove abandoned staging data from interrupted imports without touching live surcharge rows.
        RuralAreaSurchargeImportBatch::query()
            ->where('created_at', '<', now()->subDay())
            ->whereIn('status', ['uploading', 'failed'])
            ->delete();

        return RuralAreaSurchargeImportBatch::query()->create([
            'id' => (string) Str::uuid(),
            'created_by' => $adminId,
            'carrier' => Str::upper(trim((string) ($payload['carrier'] ?? 'UPS'))),
            'source_file' => trim((string) $payload['source_file']),
            'extra_charge' => round((float) $payload['extra_charge'], 2),
            'replace_existing' => (bool) ($payload['replace_existing'] ?? true),
            'expected_rows' => (int) ($payload['expected_rows'] ?? 0),
            'rows_received' => 0,
            'status' => 'uploading',
        ]);
    }

    public function appendChunk(string $batchId, int $adminId, array $rows): RuralAreaSurchargeImportBatch
    {
        return DB::transaction(function () use ($batchId, $adminId, $rows): RuralAreaSurchargeImportBatch {
            $batch = $this->ownedBatch($batchId, $adminId, true);

            if ($batch->status !== 'uploading') {
                throw ValidationException::withMessages([
                    'batch_id' => 'This import batch is no longer accepting rows.',
                ]);
            }

            $now = now();
            $records = [];

            foreach ($rows as $row) {
                try {
                    $payload = $this->normalizer->toModelPayload(array_merge($row, [
                        'carrier' => $batch->carrier,
                        'extra_charge' => $batch->extra_charge,
                    ]), [
                        'generate_source_hash' => true,
                    ]);
                } catch (Throwable $exception) {
                    $sourceRow = (int) ($row['source_row'] ?? 0);
                    throw ValidationException::withMessages([
                        'rows' => 'Spreadsheet row '.($sourceRow ?: '?').': '.$exception->getMessage(),
                    ]);
                }

                $records[] = [
                    'batch_id' => $batch->id,
                    'source_row' => max(1, (int) ($row['source_row'] ?? 1)),
                    'country' => $payload['country'],
                    'iata_code' => $payload['iata_code'],
                    'postal_code_low' => $payload['postal_code_low'],
                    'postal_code_high' => $payload['postal_code_high'],
                    'postal_code_low_normalized' => $payload['postal_code_low_normalized'],
                    'postal_code_high_normalized' => $payload['postal_code_high_normalized'],
                    'postal_code_low_numeric' => $payload['postal_code_low_numeric'],
                    'postal_code_high_numeric' => $payload['postal_code_high_numeric'],
                    'city' => $payload['city'],
                    'city_normalized' => $payload['city_normalized'],
                    'origin_surcharge' => $payload['origin_surcharge'],
                    'destination_surcharge' => $payload['destination_surcharge'],
                    'source_hash' => $payload['source_hash'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // insertOrIgnore makes network retries idempotent because each batch/hash pair is unique.
            $inserted = $records === [] ? 0 : DB::table('rural_area_surcharge_import_rows')->insertOrIgnore($records);
            $batch->increment('rows_received', $inserted);

            return $batch->fresh();
        });
    }

    public function finish(string $batchId, int $adminId): RuralAreaSurchargeImportBatch
    {
        $batch = $this->ownedBatch($batchId, $adminId);

        if ($batch->status === 'completed') {
            return $batch;
        }
        if ($batch->status !== 'uploading' || $batch->rows_received < 1) {
            throw ValidationException::withMessages([
                'batch_id' => 'The import has no staged rows to finalize.',
            ]);
        }

        if ($batch->expected_rows > 0 && $batch->rows_received !== $batch->expected_rows) {
            throw ValidationException::withMessages([
                'batch_id' => 'The import is incomplete: received '.number_format($batch->rows_received).' of '.number_format($batch->expected_rows).' rows. Re-run the workbook import.',
            ]);
        }

        try {
            DB::transaction(function () use ($batchId, $adminId): void {
                $lockedBatch = $this->ownedBatch($batchId, $adminId, true);
                $lockedBatch->update(['status' => 'processing', 'error_message' => null]);

                DB::table('rural_area_surcharge_import_rows')
                    ->where('batch_id', $lockedBatch->id)
                    ->orderBy('id')
                    ->chunkById(self::CHUNK_SIZE, function ($rows) use ($lockedBatch): void {
                        $now = now();
                        $upserts = [];

                        foreach ($rows as $row) {
                            $payload = $this->normalizer->toModelPayload([
                                'carrier' => $lockedBatch->carrier,
                                'country' => $row->country,
                                'iata_code' => $row->iata_code,
                                'postal_code_low' => $row->postal_code_low,
                                'postal_code_high' => $row->postal_code_high,
                                'city' => $row->city,
                                'origin_surcharge' => $row->origin_surcharge,
                                'destination_surcharge' => $row->destination_surcharge,
                                'extra_charge' => $lockedBatch->extra_charge,
                                'is_active' => true,
                            ], [
                                'source_file' => $lockedBatch->source_file,
                                'source_row' => (int) $row->source_row,
                                'source_hash' => $row->source_hash,
                                'import_batch_id' => $lockedBatch->id,
                            ]);

                            $payload['created_at'] = $now;
                            $payload['updated_at'] = $now;
                            $upserts[] = $payload;
                        }

                        DB::table('rural_area_surcharges')->upsert(
                            $upserts,
                            ['source_hash'],
                            [
                                'name', 'carrier', 'country', 'iata_code', 'state',
                                'postal_code_low', 'postal_code_high',
                                'postal_code_low_normalized', 'postal_code_high_normalized',
                                'postal_code_low_numeric', 'postal_code_high_numeric',
                                'postal_code_patterns', 'city', 'city_normalized',
                                'origin_surcharge', 'destination_surcharge',
                                'amount', 'extra_charge', 'is_active',
                                'source_file', 'source_row', 'import_batch_id', 'updated_at',
                            ],
                        );
                    }, 'id');

                if ($lockedBatch->replace_existing) {
                    // Replace only older spreadsheet-imported rows for this carrier.
                    // Manual rules have a NULL source_hash and are deliberately preserved.
                    DB::table('rural_area_surcharges')
                        ->where('carrier', $lockedBatch->carrier)
                        ->whereNotNull('source_hash')
                        ->where(function ($query) use ($lockedBatch): void {
                            $query->whereNull('import_batch_id')
                                ->orWhere('import_batch_id', '<>', $lockedBatch->id);
                        })
                        ->delete();
                }

                DB::table('rural_area_surcharge_import_rows')
                    ->where('batch_id', $lockedBatch->id)
                    ->delete();

                $lockedBatch->update([
                    'status' => 'completed',
                    'error_message' => null,
                ]);
            });
        } catch (Throwable $exception) {
            RuralAreaSurchargeImportBatch::query()
                ->whereKey($batchId)
                ->where('created_by', $adminId)
                ->update([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 2000, ''),
                    'updated_at' => now(),
                ]);

            throw $exception;
        }

        return $this->ownedBatch($batchId, $adminId);
    }

    private function ownedBatch(string $batchId, int $adminId, bool $lock = false): RuralAreaSurchargeImportBatch
    {
        $query = RuralAreaSurchargeImportBatch::query()
            ->whereKey($batchId)
            ->where('created_by', $adminId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $batch = $query->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'batch_id' => 'The remote-area import batch is invalid or has expired.',
            ]);
        }

        return $batch;
    }
}
