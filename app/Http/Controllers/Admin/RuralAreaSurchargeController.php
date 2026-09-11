<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RuralAreaSurchargeImportChunkRequest;
use App\Http\Requests\Admin\RuralAreaSurchargeImportFinishRequest;
use App\Http\Requests\Admin\RuralAreaSurchargeImportStartRequest;
use App\Http\Requests\Admin\RuralAreaSurchargeRequest;
use App\Models\RuralAreaSurcharge;
use App\Services\Shipping\RemoteAreaSurchargeImportService;
use App\Services\Shipping\RemoteAreaSurchargeNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RuralAreaSurchargeController extends Controller
{
    public function __construct(
        private readonly RemoteAreaSurchargeNormalizer $normalizer,
        private readonly RemoteAreaSurchargeImportService $importer,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $iataCode = strtoupper(trim((string) $request->query('iata_code')));
        $destination = trim((string) $request->query('destination_surcharge'));
        $status = trim((string) $request->query('status'));

        $query = RuralAreaSurcharge::query();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('country', 'like', '%'.$search.'%')
                    ->orWhere('iata_code', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('postal_code_low', 'like', '%'.$search.'%')
                    ->orWhere('postal_code_high', 'like', '%'.$search.'%')
                    ->orWhere('origin_surcharge', 'like', '%'.$search.'%')
                    ->orWhere('destination_surcharge', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        if ($iataCode !== '') {
            $query->where('iata_code', $iataCode);
        }

        if ($destination !== '') {
            $query->where('destination_surcharge', $destination);
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        return view('admin.rural-area-surcharges.index', [
            'surcharges' => $query
                ->orderByRaw('CASE WHEN iata_code IS NULL THEN 1 ELSE 0 END')
                ->orderBy('country')
                ->orderBy('iata_code')
                ->orderBy('postal_code_low_numeric')
                ->orderBy('postal_code_low_normalized')
                ->paginate($this->adminPerPage(50))
                ->withQueryString(),
            'countryOptions' => RuralAreaSurcharge::query()
                ->whereNotNull('iata_code')
                ->select(['country', 'iata_code'])
                ->distinct()
                ->orderBy('country')
                ->get(),
            'destinationOptions' => RuralAreaSurcharge::query()
                ->whereNotNull('destination_surcharge')
                ->where('destination_surcharge', '<>', '')
                ->distinct()
                ->orderBy('destination_surcharge')
                ->pluck('destination_surcharge'),
            'filters' => compact('search', 'iataCode', 'destination', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.rural-area-surcharges.create', [
            'surcharge' => new RuralAreaSurcharge([
                'carrier' => 'UPS',
                'country' => 'United States',
                'iata_code' => 'US',
                'origin_surcharge' => 'No',
                'destination_surcharge' => 'Remote Area Surcharge',
                'extra_charge' => 0,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(RuralAreaSurchargeRequest $request): RedirectResponse
    {
        try {
            RuralAreaSurcharge::query()->create($this->normalizer->toModelPayload($request->validated()));
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'postal_code_low' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Remote area surcharge created successfully.');
    }

    public function edit(RuralAreaSurcharge $ruralAreaSurcharge): View
    {
        return view('admin.rural-area-surcharges.edit', [
            'surcharge' => $ruralAreaSurcharge,
        ]);
    }

    public function update(RuralAreaSurchargeRequest $request, RuralAreaSurcharge $ruralAreaSurcharge): RedirectResponse
    {
        $validated = $request->validated();

        if ((bool) ($validated['legacy_mode'] ?? false)) {
            // Preserve existing pattern-based rules exactly as they were created. They continue
            // to use the legacy matcher until an administrator intentionally replaces them.
            $ruralAreaSurcharge->update([
                'name' => $validated['name'],
                'country' => $validated['country'],
                'state' => $validated['state'] ?: null,
                'postal_code_patterns' => $validated['postal_code_patterns'],
                'amount' => $validated['extra_charge'],
                'extra_charge' => $validated['extra_charge'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        } else {
            try {
                $payload = $this->normalizer->toModelPayload($validated, [
                    // Manual edits stop following an import batch so a future "replace imported data"
                    // action cannot remove a record an administrator intentionally customized.
                    'source_file' => null,
                    'source_row' => null,
                    'source_hash' => null,
                    'import_batch_id' => null,
                ]);
                $ruralAreaSurcharge->update($payload);
            } catch (Throwable $exception) {
                throw ValidationException::withMessages([
                    'postal_code_low' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Remote area surcharge updated successfully.');
    }

    public function destroy(RuralAreaSurcharge $ruralAreaSurcharge): RedirectResponse
    {
        $ruralAreaSurcharge->delete();

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Remote area surcharge removed.');
    }

    public function importStart(RuralAreaSurchargeImportStartRequest $request): JsonResponse
    {
        $batch = $this->importer->start((int) auth('admin')->id(), $request->validated());

        return response()->json([
            'batch_id' => $batch->id,
            'rows_received' => 0,
        ]);
    }

    public function importChunk(RuralAreaSurchargeImportChunkRequest $request): JsonResponse
    {
        $batch = $this->importer->appendChunk(
            (string) $request->validated('batch_id'),
            (int) auth('admin')->id(),
            $request->validated('rows'),
        );

        return response()->json([
            'batch_id' => $batch->id,
            'rows_received' => $batch->rows_received,
        ]);
    }

    public function importFinish(RuralAreaSurchargeImportFinishRequest $request): JsonResponse
    {
        $batch = $this->importer->finish(
            (string) $request->validated('batch_id'),
            (int) auth('admin')->id(),
        );

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'rows_received' => $batch->rows_received,
            'message' => number_format((int) $batch->rows_received).' remote-area rows imported successfully.',
        ]);
    }
}
