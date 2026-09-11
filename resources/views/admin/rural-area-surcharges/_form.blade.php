@php
    $isEdit = $surcharge->exists;
    $isLegacy = $isEdit && ! $surcharge->isStructuredRemoteAreaRecord();
    $isActive = old('is_active') !== null ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN) : (bool) ($surcharge->is_active ?? true);
    $originOptions = collect(['No', 'Extended Area Surcharge', 'Remote Area Surcharge', 'Remote Area Surcharge - Extended', 'Pickup Area Surcharge', 'Pickup Area Surcharge - Extended', $surcharge->origin_surcharge])->filter()->unique()->values();
    $destinationOptions = collect(['No', 'Extended Area Surcharge', 'Remote Area Surcharge', 'Remote Area Surcharge - Extended', 'Delivery Area Surcharge', 'Delivery Area Surcharge - Extended', $surcharge->destination_surcharge])->filter()->unique()->values();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    @if($isLegacy)
        <input type="hidden" name="legacy_mode" value="1">
        <x-admin.section-card title="Legacy Surcharge Rule" description="This record was created before the structured UPS remote-area list. It remains editable and continues to use the original pattern matcher.">
            <div class="admin-horizontal-form">
                <div class="admin-horizontal-form__row">
                    <label for="legacy-surcharge-name" class="admin-horizontal-form__label">Rule name</label>
                    <div class="admin-horizontal-form__field">
                        <input id="legacy-surcharge-name" type="text" name="name" value="{{ old('name', $surcharge->name) }}" class="admin-input" maxlength="150" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="legacy-surcharge-country" class="admin-horizontal-form__label">Country</label>
                    <div class="admin-horizontal-form__field">
                        <input id="legacy-surcharge-country" type="text" name="country" value="{{ old('country', $surcharge->country) }}" class="admin-input" maxlength="120" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="legacy-surcharge-state" class="admin-horizontal-form__label">State / Province</label>
                    <div class="admin-horizontal-form__field">
                        <input id="legacy-surcharge-state" type="text" name="state" value="{{ old('state', $surcharge->state) }}" class="admin-input" maxlength="120" placeholder="Optional">
                    </div>
                </div>

                <div class="admin-horizontal-form__row admin-horizontal-form__row--start">
                    <label for="legacy-surcharge-postal-patterns" class="admin-horizontal-form__label">ZIP / postal code patterns</label>
                    <div class="admin-horizontal-form__field">
                        <textarea id="legacy-surcharge-postal-patterns" name="postal_code_patterns" class="admin-textarea admin-horizontal-form__textarea" maxlength="4000" required>{{ old('postal_code_patterns', $surcharge->postal_code_patterns) }}</textarea>
                        <span class="admin-horizontal-form__help">Exact values, wildcard prefixes such as 995*, or ranges such as 99500-99999.</span>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="legacy-surcharge-extra-charge" class="admin-horizontal-form__label">Extra charge</label>
                    <div class="admin-horizontal-form__field">
                        <input id="legacy-surcharge-extra-charge" type="number" name="extra_charge" value="{{ old('extra_charge', $surcharge->effectiveExtraCharge()) }}" class="admin-input" min="0" max="999999.99" step="0.01" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <span class="admin-horizontal-form__label">Active</span>
                    <div class="admin-horizontal-form__field">
                        <label class="admin-horizontal-form__toggle">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="admin-horizontal-form__checkbox">
                            <span>Enabled</span>
                        </label>
                    </div>
                </div>
            </div>
        </x-admin.section-card>
    @else
        <input type="hidden" name="legacy_mode" value="0">
        <x-admin.section-card title="Remote Area Record" description="Fields match the UPS EAS Definitions spreadsheet. Low and High can be exact postal values, numeric ranges, or alphanumeric postcode ranges.">
            <div class="admin-horizontal-form">
                <div class="admin-horizontal-form__row">
                    <label for="remote-area-carrier" class="admin-horizontal-form__label">Carrier</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-carrier" type="text" name="carrier" value="{{ old('carrier', $surcharge->carrier ?: 'UPS') }}" class="admin-input" maxlength="40" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-country" class="admin-horizontal-form__label">Country</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-country" type="text" name="country" value="{{ old('country', $surcharge->country) }}" class="admin-input" maxlength="120" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-iata-code" class="admin-horizontal-form__label">IATA Code</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-iata-code" type="text" name="iata_code" value="{{ old('iata_code', $surcharge->iata_code) }}" class="admin-input uppercase" maxlength="2" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-postal-low" class="admin-horizontal-form__label">Postal Code Low</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-postal-low" type="text" name="postal_code_low" value="{{ old('postal_code_low', $surcharge->postal_code_low) }}" class="admin-input uppercase" maxlength="32" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-postal-high" class="admin-horizontal-form__label">Postal Code High</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-postal-high" type="text" name="postal_code_high" value="{{ old('postal_code_high', $surcharge->postal_code_high ?: $surcharge->postal_code_low) }}" class="admin-input uppercase" maxlength="32" required>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-city" class="admin-horizontal-form__label">City</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-city" type="text" name="city" value="{{ old('city', $surcharge->city) }}" class="admin-input" maxlength="160" placeholder="Optional; UPS uses city for some countries">
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-origin-surcharge" class="admin-horizontal-form__label">Origin Surcharge</label>
                    <div class="admin-horizontal-form__field">
                        <select id="remote-area-origin-surcharge" name="origin_surcharge" class="admin-input" required>
                            @foreach($originOptions as $option)
                                <option value="{{ $option }}" @selected(old('origin_surcharge', $surcharge->origin_surcharge) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <label for="remote-area-destination-surcharge" class="admin-horizontal-form__label">Destination Surcharge</label>
                    <div class="admin-horizontal-form__field">
                        <select id="remote-area-destination-surcharge" name="destination_surcharge" class="admin-input" required>
                            @foreach($destinationOptions as $option)
                                <option value="{{ $option }}" @selected(old('destination_surcharge', $surcharge->destination_surcharge) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="admin-horizontal-form__row admin-horizontal-form__row--start">
                    <label for="remote-area-extra-charge" class="admin-horizontal-form__label">Extra charge</label>
                    <div class="admin-horizontal-form__field">
                        <input id="remote-area-extra-charge" type="number" name="extra_charge" value="{{ old('extra_charge', $surcharge->effectiveExtraCharge()) }}" class="admin-input" min="0" max="999999.99" step="0.01" required>
                        <span class="admin-horizontal-form__help">This amount is added during checkout when the destination address matches this row.</span>
                    </div>
                </div>

                <div class="admin-horizontal-form__row">
                    <span class="admin-horizontal-form__label">Active</span>
                    <div class="admin-horizontal-form__field">
                        <label class="admin-horizontal-form__toggle">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="admin-horizontal-form__checkbox">
                            <span>Enabled</span>
                        </label>
                    </div>
                </div>
            </div>
        </x-admin.section-card>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.rural-area-surcharges.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Surcharge' : 'Create Surcharge' }}</button>
    </div>
</form>
