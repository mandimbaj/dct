@php
    $locales = \App\Support\WarehouseLocale::supported();
    $currentLocale = \App\Support\WarehouseLocale::current();
@endphp

<form method="GET" action="{{ route('locale.switch') }}" class="aho-locale-switcher mr-3">
    <label class="aho-locale-switcher__label" for="aho-locale-select">{{ __('aho.locale.language') }}</label>

    <select id="aho-locale-select" name="locale" aria-label="{{ __('aho.locale.language') }}" onchange="this.form.submit()">
        @foreach ($locales as $code => $label)
            <option value="{{ $code }}" @selected($currentLocale === $code)>{{ $label }}</option>
        @endforeach
    </select>
</form>
