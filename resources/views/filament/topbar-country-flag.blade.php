@php
    $countryIdentity = \App\Support\CountryContext::identityForUser(auth()->user());
    $countryIso = $countryIdentity['iso'] ?? null;
    $countryName = $countryIdentity['name'] ?? null;
@endphp

@if ($countryIso && $countryIso !== 'AFRO' && ($countryIdentity['flag_url'] ?? null))
    <img
        src="{{ $countryIdentity['flag_url'] }}"
        srcset="{{ $countryIdentity['flag_srcset'] ?? '' }}"
        class="aho-topbar-country-flag"
        alt="{{ __('aho.country_context.flag_alt', ['country' => $countryName]) }}"
        title="{{ $countryName }}"
        width="40"
        height="27"
        decoding="async"
        referrerpolicy="no-referrer"
    >
@endif
