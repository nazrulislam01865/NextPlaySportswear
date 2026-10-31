@props(['name', 'id', 'value' => '', 'callingCodes' => [], 'errorName' => null, 'dataAddressName' => null, 'placeholder' => '7123 456789'])
@php
    $displayPhone = trim((string) $value);
    $chosenDial = '+44';
    $localPhone = $displayPhone;
    foreach ($callingCodes as $code) {
        $candidate = (string) $code->dial_code;
        if ($candidate !== '' && str_starts_with($displayPhone, $candidate) && preg_match('/^'.preg_quote($candidate, '/').'(?=\s|\(|[0-9])/', $displayPhone)) {
            if (strlen($candidate) > strlen($chosenDial) || !str_starts_with($displayPhone, $chosenDial)) {
                $chosenDial = $candidate;
                $localPhone = trim(substr($displayPhone, strlen($candidate)));
            }
        }
    }
    $errorKey = $errorName ?: $name;
@endphp
<div class="np-checkout__phone np-checkout__phone-control" data-phone-control>
    <select class="np-checkout__phone-code" data-phone-dial data-nps-dropdown="phone" aria-label="Country calling code">
        @foreach($callingCodes as $code)
            <option value="{{ $code->dial_code }}" @selected($chosenDial === $code->dial_code)>{{ $code->flagEmoji() }} {{ $code->dial_code }}</option>
        @endforeach
    </select>
    <input class="np-checkout__phone-input" data-phone-local id="{{ $id }}" type="tel" autocomplete="tel-national" inputmode="tel" placeholder="{{ $placeholder }}" value="{{ $localPhone }}" required aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}" @if($errors->has($errorKey)) aria-describedby="{{ $id }}-error" @endif>
    <input type="hidden" name="{{ $name }}" value="{{ $displayPhone !== '' ? $displayPhone : '' }}" data-phone-full data-address-name="phone">
</div>
@error($errorKey)<p class="nps-error" id="{{ $id }}-error" role="alert">{{ $message }}</p>@enderror
