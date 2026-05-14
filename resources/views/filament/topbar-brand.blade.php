@php
    $logoPath = \App\Support\AhoBrand::logoPath();
@endphp

<div class="aho-topbar-brand">
    <img src="{{ asset($logoPath) }}" alt="{{ __('aho.brand.logo_alt') }}" class="aho-topbar-brand__logo">
    <div class="aho-topbar-brand__label">
        {{ __('aho.brand.app_name') }}
    </div>
    @auth
        <div class="aho-topbar-brand__welcome">
            {{ __('aho.layout.welcome', ['name' => auth()->user()->name]) }}
        </div>
    @endauth
</div>
