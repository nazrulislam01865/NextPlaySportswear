@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-bulk-quote-icons.css') }}?v=20261031bqic2">
@endpush

<x-layouts.admin
    title="Bulk Quote Icons"
    subtitle="Manage the five shared heading icons on the Bulk Quote Request form. Each icon displays in a 52 × 52px area."
>
    <div class="mx-auto max-w-7xl space-y-5 np-bqic-page">
        <section class="np-pdui-page-intro">
            <div>
                <p class="np-pdui-eyebrow">Centralized storefront controls</p>
                <h2>Bulk Quote heading icons</h2>
                <p>Set the icons once for the Bulk Quote Request form. Upload a replacement for any heading, or restore the built-in icon. Existing icons stay unchanged if no new file is selected.</p>
            </div>
            <a href="{{ route('quote.request') }}" class="btn btn-white" target="_blank" rel="noopener noreferrer">View Bulk Quote form</a>
        </section>

        @if(session('status'))
            <div class="np-bqic-feedback np-bqic-feedback--success" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->has('icons'))
            <div class="np-bqic-feedback np-bqic-feedback--error" role="alert">{{ $errors->first('icons') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.bulk-quote-icons.update') }}" enctype="multipart/form-data" class="np-pdui-editor">
            @csrf
            @method('PUT')

            <section class="np-pdui-section">
                <header class="np-pdui-section__header">
                    <div>
                        <p class="np-pdui-eyebrow">Bulk Quote Request</p>
                        <h2>Form section icons</h2>
                        <p>These icons appear beside the four form section headings. Uploaded images keep their proportions, with no colored background or frame.</p>
                    </div>
                </header>
                <div class="np-pdui-grid np-pdui-grid--2">
                    @foreach(['product', 'contact', 'delivery', 'attachments'] as $key)
                        <x-admin.bulk-quote-icon-field :icon-key="$key" :label="$slots[$key]" :url="$urls[$key]" :can-manage="$canManage" />
                    @endforeach
                </div>
            </section>

            <section class="np-pdui-section">
                <header class="np-pdui-section__header">
                    <div>
                        <p class="np-pdui-eyebrow">Assistance sidebar</p>
                        <h2>Need a hand?</h2>
                        <p>This icon appears in the customer-help card beside the form.</p>
                    </div>
                </header>
                <div class="np-pdui-grid np-pdui-grid--2">
                    <x-admin.bulk-quote-icon-field icon-key="help" :label="$slots['help']" :url="$urls['help']" :can-manage="$canManage" />
                </div>
            </section>

            <div class="np-pdui-savebar">
                <div>
                    <strong>Apply globally</strong>
                    <span>PNG, JPG, WebP or AVIF · max 2 MB per icon · 52 × 52px · no background · 1px padding</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('quote.request') }}" class="btn btn-white" target="_blank" rel="noopener noreferrer">Preview form</a>
                    @if($canManage)
                        <button type="submit" class="btn btn-primary">Save Bulk Quote Icons</button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
