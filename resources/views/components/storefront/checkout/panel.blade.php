@props(['title', 'description' => null, 'action' => null])
<section class="np-checkout__card">
    <div class="np-checkout__card-header">
        <h2>{{ $title }}</h2>
        @if($description)<p>{{ $description }}</p>@endif
        @if($action){{ $action }}@endif
    </div>
    {{ $slot }}
</section>
