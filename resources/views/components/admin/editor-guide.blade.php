@props(['heading' => 'Edit in this order', 'note' => null, 'items' => []])
<section class="np-editor-guide" aria-label="Editor navigation">
    <div class="np-editor-guide__intro">
        <span class="np-editor-guide__eyebrow">Storefront editor</span>
        <h2>{{ $heading }}</h2>
        @if($note)<p>{{ $note }}</p>@endif
    </div>
    @if(count($items))
        <nav class="np-editor-guide__steps" aria-label="Jump to settings">
            @foreach($items as $index => $item)
                <a href="#{{ $item['id'] }}" class="np-editor-guide__step">
                    <span class="np-editor-guide__number">{{ sprintf('%02d', $index + 1) }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif
</section>
