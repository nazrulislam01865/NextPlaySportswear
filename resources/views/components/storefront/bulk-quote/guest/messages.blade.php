@props(['messages', 'quote'])
@if($messages->isNotEmpty())
    <x-storefront.bulk-quote.guest.panel title="Messages & Updates" subtitle="Conversation with the NextPlay team, including your change requests.">
        <div class="np-gq-messages">
            @foreach($messages as $message)
                <div class="np-gq-message {{ $message->sender === 'guest' ? 'np-gq-message--guest' : '' }}">
                    <strong>{{ $message->sender === 'admin' ? 'NextPlay Sales' : 'You' }} · {{ \App\Support\ViewerTime::render($message->created_at, 'M j, Y · g:i A') }}</strong>
                    @if($message->kind === 'change_request')<span class="np-gq-pill">Change request</span>@endif
                    @if($message->kind === 'decision')<span class="np-gq-pill">Decision</span>@endif
                    <p>{{ $message->body }}</p>
                    @if($message->change_types)<small>Requested changes: {{ implode(', ', array_map(fn ($type) => str($type)->headline()->toString(), $message->change_types)) }}</small>@endif
                    @foreach($message->files as $file)
                        <a href="{{ route('quote.guest.file', [$quote->reference, $file]) }}" class="np-gq-attachment">Download {{ $file->original_name }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </x-storefront.bulk-quote.guest.panel>
@endif
