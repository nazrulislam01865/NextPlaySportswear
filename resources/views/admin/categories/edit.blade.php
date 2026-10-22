@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/nps-content-editor.css') }}?v=20261022c">
@endpush
@push('admin-page-scripts')
    <script src="{{ asset('js/nps-editor-file-picker.js') }}?v=20261022c" defer></script>
@endpush
<x-layouts.admin title="Edit Category">
    @include('admin.categories._form')
</x-layouts.admin>
