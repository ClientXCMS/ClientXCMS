@php
    $name = $name ?? 'phone';
    $value = $value ?? old($name, '');
    $country = strtolower($country ?? old('country', 'fr'));
    $id = $id ?? $name . '_' . substr(md5($name . random_int(0, 99999)), 0, 6);
@endphp

@if(! empty($label))
    <label for="{{ $id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">
        {{ $label }}@if(! empty($optional)) ({{ __('global.optional') }})@endif
        @if(! empty($help))
            <span class="hs-tooltip inline-block">
                <button type="button" class="hs-tooltip-toggle" aria-label="{{ $help }}">
                    <i class="bi bi-info-circle-fill text-gray-500 dark:text-gray-400"></i>
                </button>
            </span>
        @endif
    </label>
@endif

<div class="mt-2 phone-intl-wrap">
    <input
        type="tel"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        data-phone-intl
        data-initial-country="{{ $country }}"
        @if(! empty($onlyCountries))
            data-only-countries="{{ is_array($onlyCountries) ? implode(',', $onlyCountries) : $onlyCountries }}"
        @endif
        @if(! empty($required)) required @endif
        autocomplete="tel"
        inputmode="tel"
        class="input-text @error($name) border-red-500 @enderror"
    >
    @error($name)
        <span class="mt-2 text-sm text-red-500">{{ $message }}</span>
    @enderror
</div>

@once
    @vite(['resources/global/js/phone-intl.js', 'resources/global/css/phone-intl.css'])
@endonce
