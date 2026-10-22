@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/nps-content-editor.css') }}?v=20261022c">
@endpush
@push('admin-page-scripts')
    <script src="{{ asset('js/nps-editor-file-picker.js') }}?v=20261022c" defer></script>
@endpush
<x-layouts.admin title="Create Category">
    @include('admin.categories._create_form')
</x-layouts.admin>
