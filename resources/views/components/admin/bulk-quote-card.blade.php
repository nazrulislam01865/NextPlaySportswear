@props(['title', 'icon' => 'file', 'id' => null])
<section @if($id) id="{{ $id }}" @endif {{ $attributes->class(['np-bq-card']) }}>
    <header class="np-bq-card-header"><x-admin.bulk-quote-icon :name="$icon" /><h2>{{ $title }}</h2></header>
    <div class="np-bq-card-body">{{ $slot }}</div>
</section>
