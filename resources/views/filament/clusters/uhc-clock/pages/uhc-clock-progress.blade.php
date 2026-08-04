<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $countries = $this->countries();
        $countryDetails = $this->countryDetailPayloads();

        $levels = collect(['day', 'hour', 'minute', 'second'])->map(function (string $level) use ($summary) {
            $levelSummary = $summary['levels'][$level] ?? [];
            $isApcLevel = in_array($level, ['day', 'hour'], true);
            $selected = (int) ($levelSummary['selected'] ?? 0);
            $assessed = (int) ($levelSummary['assessed'] ?? 0);
            $coverage = $selected > 0 ? min(100, (int) round(($assessed / $selected) * 100)) : 0;

            return [
                'key' => $level,
                'name' => __('aho.uhc_attainment.levels.'.$level),
                'value' => $this->formatPercent($isApcLevel ? ($levelSummary['apc_remaining_average'] ?? null) : ($levelSummary['change_average'] ?? null)),
                'caption' => $isApcLevel ? __('aho.uhc_attainment.apc_required') : __('aho.uhc_attainment.average_change'),
                'assessed' => $assessed,
                'selected' => $selected,
                'coverage' => $coverage,
            ];
        });

        $stats = [
            ['label' => __('aho.uhc_attainment.selected'), 'value' => number_format($summary['selected'] ?? 0)],
            ['label' => __('aho.uhc_attainment.assessed'), 'value' => number_format($summary['assessed'] ?? 0)],
            ['label' => __('aho.uhc_attainment.targets_reached'), 'value' => $this->targetRatio($summary)],
            ['label' => __('aho.uhc_attainment.not_evaluable'), 'value' => number_format($summary['not_evaluable'] ?? 0)],
        ];
    @endphp

    <div class="aho-uhc-progress" data-uhc-progress-page>
        <section class="aho-uhc-progress__hero">
            <div class="aho-uhc-progress__intro">
                <p class="aho-uhc-progress__eyebrow">UHC Clock</p>
                <h2>{{ __('aho.uhc_attainment.country_results') }}</h2>
                <p>{{ __('aho.uhc_attainment.method_note') }}</p>

                <div class="aho-uhc-progress__guide" aria-label="{{ __('aho.uhc_attainment.level_guide') }}">
                    @foreach (['day', 'hour', 'minute', 'second'] as $level)
                        <span>
                            <strong>{{ __('aho.uhc_attainment.levels.'.$level) }}</strong>
                            {{ __('aho.uhc_attainment.level_descriptions.'.$level) }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="aho-uhc-progress__snapshot" aria-label="{{ __('aho.uhc_attainment.targets_reached') }}">
                <span>{{ __('aho.uhc_attainment.targets_reached') }}</span>
                <strong>{{ $this->targetRatio($summary) }}</strong>
            </div>
        </section>

        <section class="aho-uhc-progress__levels" aria-label="UHC Clock levels">
            @foreach ($levels as $level)
                <article class="aho-uhc-level-card aho-uhc-level-card--{{ $level['key'] }}">
                    <div class="aho-uhc-level-card__header">
                        <span class="aho-uhc-level-card__dot"></span>
                        <span>{{ $level['name'] }}</span>
                    </div>

                    <strong>{{ $level['value'] }}</strong>
                    <p>{{ $level['caption'] }}</p>

                    <div class="aho-uhc-level-card__progress" aria-hidden="true">
                        <span style="width: {{ $level['coverage'] }}%"></span>
                    </div>

                    <div class="aho-uhc-level-card__footer">
                        {{ __('aho.uhc_attainment.assessed_of_selected', [
                            'assessed' => number_format($level['assessed']),
                            'selected' => number_format($level['selected']),
                        ]) }}
                    </div>
                </article>
            @endforeach
        </section>

        <section class="aho-uhc-progress__stats" aria-label="UHC Clock summary">
            @foreach ($stats as $stat)
                <article class="aho-uhc-stat">
                    <span>{{ $stat['label'] }}</span>
                    <strong>{{ $stat['value'] }}</strong>
                </article>
            @endforeach
        </section>

        <section class="aho-uhc-table-panel">
            <div class="aho-uhc-table-panel__header">
                <div>
                    <h3>{{ __('aho.uhc_attainment.country_results') }}</h3>
                    <p>{{ __('aho.uhc_attainment.countries_count', ['count' => number_format(count($countries))]) }}</p>
                </div>

                <div class="aho-uhc-search">
                    <label class="sr-only" for="uhc-progress-search">{{ __('aho.uhc_attainment.search_country') }}</label>
                    <input
                        id="uhc-progress-search"
                        type="search"
                        data-uhc-progress-search
                        placeholder="{{ __('aho.uhc_attainment.search_country') }}"
                    >
                </div>
            </div>

            <div class="aho-uhc-table-scroll">
                <table class="aho-uhc-table">
                    <thead>
                        <tr>
                            <th>{{ __('aho.uhc_attainment.country') }}</th>
                            <th>{{ __('aho.uhc_attainment.day_apc') }}</th>
                            <th>{{ __('aho.uhc_attainment.hour_apc') }}</th>
                            <th>{{ __('aho.uhc_attainment.minute_change') }}</th>
                            <th>{{ __('aho.uhc_attainment.second_change') }}</th>
                            <th>{{ __('aho.uhc_attainment.targets_reached') }}</th>
                            <th>{{ __('aho.uhc_attainment.assessed') }}</th>
                            <th>{{ __('aho.uhc_attainment.not_evaluable') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($countries as $country)
                            @php
                                $dayMetric = $this->levelMetric($country, 'day');
                                $hourMetric = $this->levelMetric($country, 'hour');
                                $minuteMetric = $this->levelMetric($country, 'minute');
                                $secondMetric = $this->levelMetric($country, 'second');
                                $targetEvaluable = (int) ($country['target_evaluable'] ?? 0);
                                $achieved = (int) ($country['achieved'] ?? 0);
                                $targetWidth = $targetEvaluable > 0 ? min(100, (int) round(($achieved / $targetEvaluable) * 100)) : 0;
                            @endphp

                            <tr data-uhc-progress-row data-search-text="{{ \Illuminate\Support\Str::lower($country['country'] ?? '') }}">
                                <td class="aho-uhc-table__country">
                                    <button
                                        type="button"
                                        class="aho-uhc-country-button"
                                        data-uhc-country-open
                                        data-location-id="{{ $country['location_id'] }}"
                                    >
                                        {{ $country['country'] }}
                                    </button>
                                </td>
                                <td><span @class(['aho-uhc-metric', 'is-empty' => $dayMetric === 'N/A'])>{{ $dayMetric }}</span></td>
                                <td><span @class(['aho-uhc-metric', 'is-empty' => $hourMetric === 'N/A'])>{{ $hourMetric }}</span></td>
                                <td><span @class(['aho-uhc-metric', 'is-empty' => $minuteMetric === 'N/A'])>{{ $minuteMetric }}</span></td>
                                <td><span @class(['aho-uhc-metric', 'is-empty' => $secondMetric === 'N/A'])>{{ $secondMetric }}</span></td>
                                <td>
                                    <div class="aho-uhc-target">
                                        <span>{{ $this->targetRatio($country) }}</span>
                                        <div class="aho-uhc-target__bar" aria-hidden="true">
                                            <span style="width: {{ $targetWidth }}%"></span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ number_format($country['assessed'] ?? 0) }}</td>
                                <td>{{ number_format($country['not_evaluable'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="aho-uhc-table__empty">
                                    {{ __('aho.uhc_attainment.no_selection') }}
                                </td>
                            </tr>
                        @endforelse

                        <tr data-uhc-progress-empty hidden>
                            <td colspan="8" class="aho-uhc-table__empty">
                                {{ __('aho.uhc_attainment.no_filtered_countries') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="aho-uhc-dialog" data-uhc-country-dialog hidden role="dialog" aria-modal="true" aria-labelledby="uhc-country-dialog-title">
            <div class="aho-uhc-dialog__backdrop" data-uhc-country-close></div>

            <section class="aho-uhc-dialog__panel">
                <header class="aho-uhc-dialog__header">
                    <div>
                        <p>{{ __('aho.uhc_attainment.country_detail') }}</p>
                        <h3 id="uhc-country-dialog-title" data-uhc-dialog-country></h3>
                    </div>

                    <button type="button" class="aho-uhc-dialog__close" data-uhc-country-close aria-label="{{ __('aho.uhc_attainment.close_detail') }}">
                        ×
                    </button>
                </header>

                <div class="aho-uhc-dialog__stats">
                    <article>
                        <span>{{ __('aho.uhc_attainment.selected') }}</span>
                        <strong data-uhc-dialog-selected></strong>
                    </article>
                    <article>
                        <span>{{ __('aho.uhc_attainment.assessed') }}</span>
                        <strong data-uhc-dialog-assessed></strong>
                    </article>
                    <article>
                        <span>{{ __('aho.uhc_attainment.targets_reached') }}</span>
                        <strong data-uhc-dialog-targets></strong>
                    </article>
                    <article>
                        <span>{{ __('aho.uhc_attainment.not_evaluable') }}</span>
                        <strong data-uhc-dialog-missing></strong>
                    </article>
                </div>

                <div class="aho-uhc-dialog__levels" data-uhc-dialog-levels></div>

                <div class="aho-uhc-dialog__columns">
                    <section>
                        <h4>{{ __('aho.uhc_attainment.assessed_examples') }}</h4>
                        <div class="aho-uhc-dialog__list" data-uhc-dialog-assessed-list></div>
                    </section>

                    <section>
                        <h4>{{ __('aho.uhc_attainment.missing_examples') }}</h4>
                        <div class="aho-uhc-dialog__list" data-uhc-dialog-missing-list></div>
                    </section>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const countryDetails = @js($countryDetails);
            const emptyAssessed = @js(__('aho.uhc_attainment.no_assessed_examples'));
            const emptyMissing = @js(__('aho.uhc_attainment.no_missing_examples'));

            const bootUhcProgressPage = () => {
                const page = document.querySelector('[data-uhc-progress-page]');

                if (!page || page.dataset.ready === '1') {
                    return;
                }

                page.dataset.ready = '1';

                const searchInput = page.querySelector('[data-uhc-progress-search]');
                const rows = Array.from(page.querySelectorAll('[data-uhc-progress-row]'));
                const emptyRow = page.querySelector('[data-uhc-progress-empty]');
                const dialog = page.querySelector('[data-uhc-country-dialog]');
                const countryButtons = Array.from(page.querySelectorAll('[data-uhc-country-open]'));

                const applySearch = () => {
                    const term = (searchInput?.value || '').trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const shouldShow = term === '' || (row.dataset.searchText || '').includes(term);
                        row.hidden = !shouldShow;

                        if (shouldShow) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyRow) {
                        emptyRow.hidden = visibleCount !== 0 || rows.length === 0;
                    }
                };

                const setText = (selector, value) => {
                    const node = dialog?.querySelector(selector);

                    if (node) {
                        node.textContent = value || 'N/A';
                    }
                };

                const card = (label, value, meta = null) => {
                    const article = document.createElement('article');
                    article.className = 'aho-uhc-dialog-level';

                    const labelNode = document.createElement('span');
                    labelNode.textContent = label;

                    const valueNode = document.createElement('strong');
                    valueNode.textContent = value;

                    article.append(labelNode, valueNode);

                    if (meta) {
                        const metaNode = document.createElement('em');
                        metaNode.textContent = meta;
                        article.append(metaNode);
                    }

                    return article;
                };

                const listItem = (item, type) => {
                    const wrapper = document.createElement('article');
                    wrapper.className = 'aho-uhc-dialog-item';

                    const title = document.createElement('strong');
                    title.textContent = item.title;

                    const badge = document.createElement('span');
                    badge.textContent = item.level;

                    const head = document.createElement('div');
                    head.className = 'aho-uhc-dialog-item__head';
                    head.append(title, badge);
                    wrapper.append(head);

                    const lines = type === 'assessed'
                        ? [item.baseline, item.current, item.change, item.remaining, item.target]
                        : [item.reason, item.facts];

                    lines.filter(Boolean).forEach((line) => {
                        const p = document.createElement('p');

                        if (typeof line === 'object') {
                            p.className = 'aho-uhc-dialog-value-line';

                            const label = document.createElement('span');
                            label.className = 'aho-uhc-dialog-value-line__label';
                            label.textContent = `${line.label}:`;

                            const value = document.createElement('strong');
                            value.className = 'aho-uhc-dialog-value-line__value';
                            value.textContent = line.value || 'N/A';

                            const info = document.createElement('button');
                            info.type = 'button';
                            info.className = 'aho-uhc-dialog-value-line__info';
                            info.textContent = 'i';
                            info.title = line.tooltip || '';
                            info.setAttribute('aria-label', line.tooltip || '');

                            p.append(label, value, info);
                        } else {
                            p.textContent = line;
                        }

                        wrapper.append(p);
                    });

                    return wrapper;
                };

                const renderList = (selector, items, type) => {
                    const container = dialog?.querySelector(selector);

                    if (!container) {
                        return;
                    }

                    container.replaceChildren();

                    if (!items?.length) {
                        const empty = document.createElement('p');
                        empty.className = 'aho-uhc-dialog-empty';
                        empty.textContent = type === 'assessed' ? emptyAssessed : emptyMissing;
                        container.append(empty);

                        return;
                    }

                    items.forEach((item) => container.append(listItem(item, type)));
                };

                const openDialog = (detail) => {
                    if (!dialog || !detail) {
                        return;
                    }

                    setText('[data-uhc-dialog-country]', detail.country);
                    setText('[data-uhc-dialog-selected]', detail.selected);
                    setText('[data-uhc-dialog-assessed]', detail.assessed);
                    setText('[data-uhc-dialog-targets]', detail.targetRatio);
                    setText('[data-uhc-dialog-missing]', detail.notEvaluable);

                    const levels = dialog.querySelector('[data-uhc-dialog-levels]');
                    levels?.replaceChildren(...(detail.levels || []).map((level) => card(
                        level.name,
                        level.metric,
                        `${level.assessed} / ${level.selected}`
                    )));

                    renderList('[data-uhc-dialog-assessed-list]', detail.assessedExamples, 'assessed');
                    renderList('[data-uhc-dialog-missing-list]', detail.missingExamples, 'missing');

                    dialog.hidden = false;
                    document.body.classList.add('aho-uhc-dialog-open');
                };

                const closeDialog = () => {
                    if (!dialog) {
                        return;
                    }

                    dialog.hidden = true;
                    document.body.classList.remove('aho-uhc-dialog-open');
                };

                searchInput?.addEventListener('input', applySearch);
                countryButtons.forEach((button) => {
                    button.addEventListener('click', () => openDialog(countryDetails[button.dataset.locationId]));
                });
                dialog?.querySelectorAll('[data-uhc-country-close]').forEach((button) => {
                    button.addEventListener('click', closeDialog);
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !dialog?.hidden) {
                        closeDialog();
                    }
                });
                applySearch();
            };

            document.addEventListener('DOMContentLoaded', bootUhcProgressPage);
            document.addEventListener('livewire:navigated', bootUhcProgressPage);
            bootUhcProgressPage();
        })();
    </script>
</x-filament-panels::page>
