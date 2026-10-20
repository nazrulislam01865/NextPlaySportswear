<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class FlowTrackOrderProgressController extends Controller
{
    public function __invoke(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate([
            'source_order_id' => ['required', 'integer', 'min:1'],
            'flow_job_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'stage' => ['nullable', 'string', 'max:120'],
            'progress' => ['nullable', 'integer', 'between:0,100'],
            // Accept old second-precision callbacks as well as the microsecond precision sender.
            'changed_at' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s.uP'],
        ]);

        $incomingAt = CarbonImmutable::parse($data['changed_at'])->utc();
        if ($incomingAt->greaterThan(now()->addMinutes(5))) {
            return response()->json(['ok' => false, 'message' => 'Future progress timestamp rejected.'], 422);
        }

        $updated = DB::table('orders')
            ->where('id', $data['source_order_id'])
            ->where('order_number', $orderNumber)
            ->where(function ($query) use ($data): void {
                $query->whereNull('flowtrack_job_id')
                    ->orWhere('flowtrack_job_id', (string) $data['flow_job_id']);
            })
            ->where(function ($query) use ($incomingAt): void {
                $query->whereNull('flowtrack_operational_updated_at')
                    ->orWhere('flowtrack_operational_updated_at', '<', $incomingAt->format('Y-m-d H:i:s.u'));
            })
            ->update([
                'flowtrack_job_id' => (string) $data['flow_job_id'],
                'flowtrack_operational_status' => $data['status'],
                'flowtrack_operational_stage' => $data['stage'] ?? null,
                'flowtrack_operational_progress' => $data['progress'] ?? null,
                'flowtrack_operational_updated_at' => $incomingAt->format('Y-m-d H:i:s.u'),
            ]);

        if ($updated) {
            return response()->json(['ok' => true, 'applied' => true]);
        }

        // Deliberately do not reveal whether this is an unknown order, a wrong
        // FlowTracker job, or an old duplicate update to the API caller.
        $known = Order::query()->whereKey($data['source_order_id'])
            ->where('order_number', $orderNumber)
            ->where('flowtrack_job_id', (string) $data['flow_job_id'])->exists();

        return response()->json(['ok' => $known, 'applied' => false], $known ? 200 : 409);
    }
}
