@php
    $logoPath = \App\Support\AhoBrand::whiteLogoPath();
@endphp

<div class="aho-topbar-brand">
    <img src="{{ asset($logoPath) }}" alt="{{ __('aho.brand.logo_alt') }}" class="aho-topbar-brand__logo">
    <div class="aho-topbar-brand__label">
        <span class="aho-topbar-brand__app">{{ __('aho.brand.app_name') }}</span>
        @auth
            <span class="aho-topbar-welcome">
                <span class="aho-topbar-welcome__separator" aria-hidden="true">|</span>
                <span class="aho-topbar-welcome__text">{{ __('aho.layout.welcome', ['name' => auth()->user()->name]) }}</span>
            </span>
        @endauth
    </div>
</div>
