@props(['quote', 'files', 'compact' => false])
@if($files->isEmpty())
    <p class="np-gq-muted np-gq-empty">No quotation files have been shared yet.</p>
@else
    <div class="np-gq-files {{ $compact ? 'np-gq-files--compact' : '' }}">
        @unless($compact)
            <div class="np-gq-files-heading" aria-hidden="true">
                <span>File Name</span><span>File Type</span><span>Uploaded On</span><span>Actions</span>
            </div>
        @endunless
        @foreach($files as $file)
            @php
                $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
                $fileType = in_array($extension, ['pdf', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'], true) ? strtoupper($extension) : 'FILE';
                $canPreview = in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'], true);
            @endphp
            <div class="np-gq-file">
                <div class="np-gq-file-identity">
                    <span class="np-gq-filetype np-gq-filetype--{{ $extension }}" aria-label="{{ $fileType }} file">{{ $fileType }}</span>
                    <div class="np-gq-file-text"><strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                        <small>{{ $fileType }} · {{ number_format($file->size / 1024, 0) }} KB</small>
                    </div>
                </div>
                @unless($compact)
                    <span class="np-gq-file-kind">{{ $fileType }}</span>
                    <span class="np-gq-file-date">{{ \App\Support\ViewerTime::render($file->created_at, 'M j, Y') }}<small>{{ \App\Support\ViewerTime::render($file->created_at, 'g:i A') }}</small></span>
                @endunless
                <div class="np-gq-file-actions">
                    @if($canPreview)
                        <a class="btn btn-white np-gq-file-button" href="{{ route('quote.guest.file', [$quote->reference, $file, 'view' => 1]) }}" target="_blank" rel="noopener noreferrer"><x-storefront.bulk-quote.guest.icon name="eye" size="17" /> View</a>
                    @endif
                    <a class="btn {{ $compact ? 'btn-white' : 'btn-red' }} np-gq-file-button" href="{{ route('quote.guest.file', [$quote->reference, $file]) }}"><x-storefront.bulk-quote.guest.icon name="download" size="17" /> Download</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
