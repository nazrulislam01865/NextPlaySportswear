{{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}
{{ $emailMessage->heading }}
{{ $emailMessage->key === 'admin.confirmation-auto-forward' ? 'Automatically forwarded request' : 'Administrator-forwarded request' }}

@foreach ($emailMessage->introLines as $line)
{!! $line !!}
@endforeach

{!! app(\App\Services\Email\ForwardingEmailFormatter::class)->sectionsText($emailMessage->sections) !!}

@if (filled($emailMessage->actionText) && filled($emailMessage->actionUrl))
{{ $emailMessage->actionText }}: {!! $emailMessage->actionUrl !!}
@endif

@foreach ($emailMessage->outroLines as $line)
{!! $line !!}
@endforeach

Internal forwarding notification from {{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}.
