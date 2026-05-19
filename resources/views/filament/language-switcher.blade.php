@php
    $locales = \App\Support\WarehouseLocale::supported();
    $currentLocale = \App\Support\WarehouseLocale::current();
@endphp

<div class="aho-locale-switcher mr-3">
    <label class="aho-locale-switcher__label" for="aho-locale-select">{{ __('aho.locale.language') }}</label>

    <select id="aho-locale-select" aria-label="{{ __('aho.locale.language') }}" onchange="window.location.href='{{ url('/locale') }}/'+this.value">
        @foreach ($locales as $code => $label)
            <option value="{{ $code }}" @selected($currentLocale === $code)>{{ $label }}</option>
        @endforeach
    </select>
</div>
