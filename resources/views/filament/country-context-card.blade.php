@php
    $countryContext = \App\Support\CountryContext::forUser(auth()->user());
@endphp

@if ($countryContext)
    <aside class="aho-country-card" aria-label="{{ $countryContext['name'] }}">

        @if ($countryContext['svg_html'] ?? null)
            <div class="aho-country-card__visual">
                <div class="aho-country-card__svg" aria-hidden="true">
                    {!! $countryContext['svg_html'] !!}
                </div>
            </div>

        @else
            <div class="aho-country-card__fallback" aria-hidden="true">
                {{ $countryContext['iso'] ?? 'AFRO' }}
            </div>
        @endif

        <strong class="aho-country-card__name">{{ $countryContext['name'] }}</strong>

    </aside>
@endif
