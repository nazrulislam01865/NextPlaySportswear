@props([
    'title' => 'FlowTrack Sync',
    'description' => 'Integration delivery state.',
    'status' => null,
    'attempts' => 0,
    'remoteNumber' => null,
    'remoteNumberLabel' => 'FlowTrack Number',
    'remoteId' => null,
    'remoteIdLabel' => 'FlowTrack ID',
    'jobId' => null,
    'lastAttemptAt' => null,
    'syncedAt' => null,
    'error' => null,
    'response' => [],
    'retryAction' => null,
    'canRetry' => false,
    'retryLabel' => 'Retry Sync',
])

<x-admin.section-card :title="$title" :description="$description">
    <div class="flex flex-col gap-5">
        <dl class="admin-detail-grid">
            <x-admin.detail-field label="Sync Status">
                <x-admin.status-pill :status="$status ?: 'pending'" />
            </x-admin.detail-field>
            <x-admin.detail-field label="Sync Attempts">{{ number_format((int) $attempts) }}</x-admin.detail-field>
            <x-admin.detail-field :label="$remoteNumberLabel">{{ $remoteNumber ?: '—' }}</x-admin.detail-field>
            <x-admin.detail-field :label="$remoteIdLabel">{{ $remoteId ?: '—' }}</x-admin.detail-field>
            @if($jobId)
                <x-admin.detail-field label="FlowTrack Job ID">{{ $jobId }}</x-admin.detail-field>
            @endif
            <x-admin.detail-field label="Last Attempt">{{ $lastAttemptAt?->format('M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
            <x-admin.detail-field label="Synced At">{{ $syncedAt?->format('M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
            <x-admin.detail-field label="Last Sync Error" :wide="true">
                <div class="whitespace-pre-line {{ $error ? 'text-red-700' : 'text-slate-600' }}">{{ $error ?: 'No sync error.' }}</div>
            </x-admin.detail-field>
        </dl>

        @if($retryAction && $canRetry)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm leading-6 text-slate-600">Retry reuses the same idempotency contract so the FlowTrack receiver can safely recognize repeat delivery attempts instead of treating every retry as a new request.</p>
                <form method="POST" action="{{ $retryAction }}">
                    @csrf
                    <button class="btn btn-red" type="submit" @disabled($status === 'syncing')>{{ $status === 'syncing' ? 'Sync in progress' : $retryLabel }}</button>
                </form>
            </div>
        @endif

        @if(is_array($response) && $response !== [])
            <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer text-sm font-black text-brand-dark">Technical response</summary>
                <pre class="admin-code-block mt-3">{{ json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        @endif
    </div>
</x-admin.section-card>
