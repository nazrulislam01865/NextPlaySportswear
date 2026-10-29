@props(['inputId', 'name' => 'file', 'label' => 'CSV or Excel file'])
<div class="np-geo-import-file">
    <label for="{{ $inputId }}" class="np-geo-import-label">{{ $label }}</label>
    <div class="np-geo-upload-surface">
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="file"
            accept=".csv,.txt,.xls,.xlsx"
            required
            class="np-geo-file-input"
            aria-describedby="{{ $inputId }}-help {{ $inputId }}-selected"
        >
        <div class="np-geo-upload-content">
            <span class="btn btn-white np-geo-upload-button" aria-hidden="true">Choose file</span>
            <span id="{{ $inputId }}-selected" class="np-geo-selected-file" role="status" aria-live="polite">No file selected</span>
        </div>
    </div>
    <span id="{{ $inputId }}-help" class="np-geo-file-hint">CSV, XLS or XLSX &middot; Max 4 MB</span>
</div>

@once
    @push('admin-page-scripts')
        <script>
            document.querySelectorAll('.np-geo-file-input').forEach((input) => {
                input.addEventListener('change', () => {
                    const label = input.closest('.np-geo-upload-surface')?.querySelector('.np-geo-selected-file');
                    if (!label) return;
                    label.textContent = input.files?.[0]?.name || 'No file selected';
                    input.closest('.np-geo-upload-surface')?.classList.toggle('has-file', Boolean(input.files?.length));
                });
            });
        </script>
    @endpush
@endonce
