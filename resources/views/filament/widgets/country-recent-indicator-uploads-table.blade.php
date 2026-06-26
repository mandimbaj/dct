<x-filament-widgets::widget class="aho-country-indicator-widget">
    <style>
        .aho-country-indicator-widget {
            display: flex;
            height: 38.25rem;
            max-height: 38.25rem;
            min-height: 38.25rem;
        }

        .aho-country-indicator-widget .fi-section {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            height: 38.25rem;
            max-height: 38.25rem;
            min-height: 38.25rem;
            overflow: hidden;
        }

        .aho-country-indicator-widget .fi-section-content-ctn,
        .aho-country-indicator-widget .fi-section-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .aho-country-indicator-table {
            border-collapse: separate;
            border-spacing: 0 .28rem;
            font-size: .79rem;
            width: 100%;
        }

        .aho-country-indicator-table-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: auto;
            overflow-y: auto;
            padding-right: .2rem;
            scrollbar-width: thin;
        }

        .aho-country-indicator-table thead th {
            color: rgb(107 114 128);
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .045em;
            padding: 0 .55rem .1rem;
            text-align: left;
            text-transform: uppercase;
        }

        .aho-country-indicator-table tbody tr {
            background: color-mix(in srgb, var(--aho-row-color) 9%, white);
            box-shadow: inset 4px 0 0 var(--aho-row-color), 0 1px 2px rgb(15 23 42 / 7%);
        }

        .aho-country-indicator-table tbody td {
            border-bottom: 1px solid rgb(226 232 240 / 80%);
            border-top: 1px solid rgb(226 232 240 / 80%);
            padding: .42rem .55rem;
            vertical-align: middle;
        }

        .aho-country-indicator-table tbody td:first-child {
            border-bottom-left-radius: .85rem;
            border-left: 1px solid rgb(226 232 240 / 80%);
            border-top-left-radius: .85rem;
        }

        .aho-country-indicator-table tbody td:last-child {
            border-bottom-right-radius: .85rem;
            border-right: 1px solid rgb(226 232 240 / 80%);
            border-top-right-radius: .85rem;
        }

        .aho-country-indicator-rank {
            align-items: center;
            background: var(--aho-row-color);
            border-radius: 999px;
            color: white;
            display: inline-flex;
            font-size: .66rem;
            font-weight: 800;
            height: 1.35rem;
            justify-content: center;
            width: 1.35rem;
        }

        .aho-country-indicator-name {
            color: rgb(15 23 42);
            display: -webkit-box;
            font-weight: 700;
            line-height: 1rem;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .aho-country-indicator-muted {
            color: rgb(100 116 139);
            font-size: .78rem;
        }

        .dark .aho-country-indicator-table thead th {
            color: rgb(156 163 175);
        }

        .dark .aho-country-indicator-table tbody tr {
            background: color-mix(in srgb, var(--aho-row-color) 18%, rgb(17 24 39));
            box-shadow: inset 4px 0 0 var(--aho-row-color), 0 1px 2px rgb(0 0 0 / 20%);
        }

        .dark .aho-country-indicator-table tbody td {
            border-color: rgb(55 65 81 / 80%);
        }

        .dark .aho-country-indicator-name {
            color: rgb(243 244 246);
        }

        .dark .aho-country-indicator-muted {
            color: rgb(209 213 219);
        }
    </style>

    <x-filament::section
        :description="$description"
        :heading="$heading"
    >
        @if ($rows->isNotEmpty())
            <div class="aho-country-indicator-table-scroll">
                <table class="aho-country-indicator-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('aho.fields.indicator') }}</th>
                            <th>{{ __('aho.fields.uploaded_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr style="--aho-row-color: {{ $row['color'] }}">
                                <td>
                                    <span class="aho-country-indicator-rank">{{ $row['rank'] }}</span>
                                </td>
                                <td>
                                    <div class="aho-country-indicator-name">{{ $row['indicator'] }}</div>
                                </td>
                                <td class="aho-country-indicator-muted">{{ $row['uploaded_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                {{ __('aho.charts.no_recent_country_indicator_data') }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
