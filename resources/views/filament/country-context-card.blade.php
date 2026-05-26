@php
    $countryContext = \App\Support\CountryContext::forUser(auth()->user());
@endphp

@if ($countryContext)
    <aside class="aho-country-card" aria-label="{{ $countryContext['name'] }}">

        @if ($countryContext['svg_html'] ?? null)
            {{-- Country SVG map (blue) + flag side by side --}}
            <div class="aho-country-card__visual">
                <div class="aho-country-card__svg" aria-hidden="true">
                    {!! $countryContext['svg_html'] !!}
                </div>
                @if ($countryContext['flag_url'] ?? null)
                    <img
                        src="{{ $countryContext['flag_url'] }}"
                        srcset="{{ $countryContext['flag_srcset'] ?? '' }}"
                        alt="{{ $countryContext['name'] }}"
                        class="aho-country-card__flag"
                        loading="lazy"
                    >
                @endif
            </div>

        @elseif ($countryContext['map_url'] ?? null)
            {{-- Africa overview map (super admin) --}}
            <div class="aho-country-card__map">
                <iframe
                    src="{{ $countryContext['map_url'] }}"
                    title="{{ $countryContext['name'] }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>

        @else
            {{-- Fallback: flag only --}}
            @if ($countryContext['flag_url'] ?? null)
                <img
                    src="{{ $countryContext['flag_url'] }}"
                    srcset="{{ $countryContext['flag_srcset'] ?? '' }}"
                    alt="{{ $countryContext['name'] }}"
                    class="aho-country-card__flag aho-country-card__flag--solo"
                    loading="lazy"
                >
            @endif
        @endif

        <strong class="aho-country-card__name">{{ $countryContext['name'] }}</strong>

    </aside>
@endif
