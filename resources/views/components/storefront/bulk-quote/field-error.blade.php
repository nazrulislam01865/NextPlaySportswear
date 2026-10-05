@props(['name'])

@error($name)
    <p class="bulk-quote-field-error" role="alert">{{ $message }}</p>
@enderror
