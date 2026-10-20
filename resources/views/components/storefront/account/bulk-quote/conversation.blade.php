@props(['quote', 'messages'])
<section class="np-rq-card np-rq-conversation" aria-labelledby="np-rq-conversation-title">
    <h2 id="np-rq-conversation-title">Conversation &amp; Feedback</h2>
    <div class="np-rq-conversation-list">
        @forelse($messages as $message)
            <article class="np-rq-message">
                <span class="np-rq-avatar">{{ $message->sender === 'admin' ? 'NP' : 'YOU' }}</span>
                <div class="np-rq-message-text">
                    <div class="np-rq-message-heading"><strong>{{ $message->sender === 'admin' ? 'NextPlay Sales' : 'You' }}</strong><small>{{ \App\Support\ViewerTime::render($message->created_at, 'M j, Y · g:i A') }}</small></div>
                    <p>{{ $message->body }}</p>
                    @if($message->change_types)<small>Changes: {{ implode(', ', $message->change_types) }}</small>@endif
                    @foreach($message->files as $file)
                        <a class="np-rq-message-attachment" href="{{ route('account.bulk-quotes.file', [$quote->reference, $file]) }}"><x-storefront.account.icon name="downloads" :size="14" />{{ $file->original_name }}</a>
                    @endforeach
                </div>
            </article>
        @empty
            <p class="np-rq-muted">No messages yet. When our team replies, the conversation will appear here.</p>
        @endforelse
    </div>
</section>
