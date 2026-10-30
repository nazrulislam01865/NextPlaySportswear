@props(['name'])

@error($name)
    <p class="bulk-quote-field-error nps-error" role="alert">{{ $message }}</p>
@enderror
