@php
    $logoPath = \App\Support\AhoBrand::whiteLogoPath();
    $welcomeText = auth()->check()
        ? __('aho.layout.welcome', ['name' => auth()->user()->name])
        : null;
@endphp

<div class="aho-topbar-brand">
    <img src="{{ asset($logoPath) }}" alt="{{ __('aho.brand.logo_alt') }}" class="aho-topbar-brand__logo">
    <div class="aho-topbar-brand__label">
        <span class="aho-topbar-brand__app">{{ __('aho.brand.app_name') }}</span>
        @if ($welcomeText)
            <span class="aho-topbar-welcome">
                <span class="aho-topbar-welcome__separator" aria-hidden="true">|</span>
                <span class="aho-topbar-welcome__text" title="{{ $welcomeText }}">{{ $welcomeText }}</span>
            </span>
        @endif
    </div>
</div>
