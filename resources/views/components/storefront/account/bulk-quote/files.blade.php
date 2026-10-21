@props(['quote','files','mode' => 'full'])
@if($files->isEmpty())
    <p class="np-rq-muted">No offer files have been shared yet. Files will be available here after FlowTracker sends them or NextPlay publishes your quotation.</p>
@else
    <div class="np-rq-file-list {{ $mode === 'small' ? 'np-rq-file-list--small' : '' }}">
        @foreach($files as $file)
            @php
                $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
                $type = in_array($ext, ['pdf','xlsx','xls','jpg','jpeg','png'], true) ? $ext : 'file';
                $preview = in_array($ext, ['pdf','jpg','jpeg','png'], true);
            @endphp
            <div class="np-rq-file">
                <span class="np-rq-file-icon np-rq-file-icon--{{ $type }}">{{ strtoupper($type === 'jpeg' || $type === 'jpg' ? 'IMG' : $type) }}</span>
                <div class="np-rq-file-meta"><strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong><small>{{ strtoupper($type) }} · {{ number_format($file->size / 1024) }} KB</small></div>
                <div class="np-rq-file-actions">
                    @if($preview)<a class="btn btn-white" target="_blank" rel="noopener noreferrer" href="{{ route('account.bulk-quotes.file', [$quote->reference, $file, 'view' => 1]) }}">View</a>@endif
                    <a class="btn btn-white" href="{{ route('account.bulk-quotes.file', [$quote->reference, $file]) }}"><x-storefront.account.icon name="downloads" :size="15" />Download</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
