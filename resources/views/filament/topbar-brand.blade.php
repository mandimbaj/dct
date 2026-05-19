@php
    $logoPath = \App\Support\AhoBrand::logoPath();
@endphp

<div class="aho-topbar-brand">
    <img src="{{ asset($logoPath) }}" alt="{{ __('aho.brand.logo_alt') }}" class="aho-topbar-brand__logo">
    <div class="aho-topbar-brand__label">
        {{ __('aho.brand.app_name') }}
        @auth
            <span class="aho-topbar-welcome">| {{ __('aho.layout.welcome', ['name' => auth()->user()->name]) }}</span>
        @endauth
    </div>
</div>
