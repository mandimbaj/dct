@if (config('services.microsoft_entra.enabled'))
    @php
        $country = request()->route('country') ?: 'af';
    @endphp

    <div class="aho-entra-login">
        @if ($message = $errors->first('microsoft_entra'))
            <div class="aho-entra-login__error" role="alert">{{ $message }}</div>
        @endif

        <p class="aho-entra-login__description">
            {{ __('aho.microsoft_entra.description') }}
        </p>

        <x-filament::button
            tag="a"
            :href="route('microsoft-entra.redirect', ['country' => $country])"
            icon="heroicon-o-building-office-2"
            size="lg"
            :spa-mode="false"
            class="aho-entra-login__button"
        >
            {{ __('aho.microsoft_entra.button') }}
        </x-filament::button>

        @if (config('services.microsoft_entra.local_login_enabled', true))
            <div class="aho-entra-login__divider">
                <span>{{ __('aho.microsoft_entra.local_login_divider') }}</span>
            </div>
        @endif
    </div>
@endif
