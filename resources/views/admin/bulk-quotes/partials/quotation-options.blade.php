@php
    $sampleOptions = $optionKind === 'sample_options';
    $optionLabel = $sampleOptions ? 'Sample options' : 'Product options';
@endphp
<div class="np-quote-inline-options" data-option-kind="{{ $optionKind }}" data-line-index="{{ $i }}">
    {{-- A sentinel distinguishes deliberately cleared sample checkbox groups from legacy clients
         that never sent separate sample options at all. --}}
    @if($sampleOptions)<input type="hidden" name="lines[{{ $i }}][sample_options][_submitted]" value="1">@endif
    <span class="np-quote-options-caption">{{ $optionLabel }}</span>
    @foreach($line['groups'] as $group)
        @php
            $groupId = $group['id'];
            $savedOptions = (array) ($stored[$optionKind] ?? []);
            $defaultValue = $sampleOptions ? ($stored['options'][$groupId] ?? $group['selected']) : $group['selected'];
            $selectedOption = old("lines.$i.$optionKind.$groupId", $savedOptions[$groupId] ?? $defaultValue);
            $chosen = array_map('strval', (array) $selectedOption);
            $isMissing = $group['required'] && count(array_filter($chosen, fn ($v) => $v !== '')) === 0;
            $name = "lines[$i][$optionKind][$groupId]";
        @endphp
        <div class="np-quote-option-field" @if($isMissing) data-unselected="1" @endif>
            <label for="np-quote-{{ $optionKind }}-{{ $i }}-{{ $groupId }}">{{ $group['label'] }} @if($group['required']) <span class="np-quote-star" aria-label="required">*</span> @endif</label>
            @if($group['type'] === 'checkbox')
                <div class="np-quote-option-checks" id="np-quote-{{ $optionKind }}-{{ $i }}-{{ $groupId }}">
                    @foreach($group['values'] as $choice)
                        <label class="np-quote-check">
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $choice['id'] }}"
                                   @checked(in_array((string) $choice['id'], $chosen, true))
                                   @disabled($group['fixed'])
                                   data-option-kind="{{ $optionKind }}" data-option-group="{{ $groupId }}" data-option-line="{{ $i }}">
                            <span>{{ $choice['label'] }} @if($choice['price']) (+${{ number_format($choice['price'], 2) }} {{ $choice['charge_type'] === 'fixed_order' ? '/order' : '/pc' }}) @endif</span>
                        </label>
                    @endforeach
                    @if($group['fixed']) @foreach($chosen as $id) <input type="hidden" name="{{ $name }}[]" value="{{ $id }}"> @endforeach @endif
                </div>
            @else
                <select class="admin-input np-quote-option-select" id="np-quote-{{ $optionKind }}-{{ $i }}-{{ $groupId }}"
                        name="{{ $name }}" @disabled($group['fixed'])
                        data-option-kind="{{ $optionKind }}" data-option-group="{{ $groupId }}" data-option-line="{{ $i }}">
                    @unless($group['fixed'])<option value="">Choose {{ $group['label'] }}</option>@endunless
                    @foreach($group['values'] as $choice)
                        <option value="{{ $choice['id'] }}" @selected((string) $selectedOption === (string) $choice['id'])>
                            {{ $choice['label'] }}@if($choice['price']) · +${{ number_format($choice['price'], 2) }} {{ $choice['charge_type'] === 'fixed_order' ? '/order' : '/pc' }}@endif
                        </option>
                    @endforeach
                </select>
                @if($group['fixed'])<input type="hidden" name="{{ $name }}" value="{{ $selectedOption }}">@endif
            @endif
        </div>
    @endforeach
</div>
