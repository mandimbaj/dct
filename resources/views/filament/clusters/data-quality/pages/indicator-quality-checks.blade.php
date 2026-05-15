<x-filament-panels::page>
    @php
        $issues = $this->getIssues();
        $summary = $this->getSummary();
        $totalIssues = $summary['errors'] + $summary['warnings'];
    @endphp

    <div class="aho-quality" data-quality-page>
        <section class="aho-quality__hero">
            <div>
                <p class="aho-quality__eyebrow">{{ __('aho.menus.data_quality') }}</p>
                <h2 class="aho-quality__title">{{ __('aho.quality.title') }}</h2>
                <p class="aho-quality__subtitle">{{ __('aho.quality.subtitle') }}</p>
            </div>

            <div class="aho-quality__sample">
                <span>{{ __('aho.quality.checked_sample') }}</span>
                <strong>{{ number_format($summary['checked']) }}</strong>
            </div>
        </section>

        <section class="aho-quality__stats" aria-label="{{ __('aho.quality.filters') }}">
            <button type="button" class="aho-quality-stat is-active" data-quality-filter="all">
                <span>{{ __('aho.quality.all_issues') }}</span>
                <strong>{{ number_format($totalIssues) }}</strong>
            </button>

            <button type="button" class="aho-quality-stat aho-quality-stat--error" data-quality-filter="error">
                <span>{{ __('aho.quality.errors') }}</span>
                <strong>{{ number_format($summary['errors']) }}</strong>
            </button>

            <button type="button" class="aho-quality-stat aho-quality-stat--warning" data-quality-filter="warning">
                <span>{{ __('aho.quality.warnings') }}</span>
                <strong>{{ number_format($summary['warnings']) }}</strong>
            </button>
        </section>

        <section class="aho-quality-panel">
            <header class="aho-quality-panel__header">
                <div>
                    <h3>{{ __('aho.quality.latest_issues') }}</h3>
                    <p>{{ __('aho.quality.review_hint') }}</p>
                </div>

                <div class="aho-quality-search">
                    <label class="sr-only" for="quality-search">{{ __('aho.quality.search_placeholder') }}</label>
                    <input
                        type="search"
                        id="quality-search"
                        data-quality-search
                        autocomplete="off"
                        placeholder="{{ __('aho.quality.search_placeholder') }}"
                    >
                    <button type="button" data-quality-clear>{{ __('aho.quality.clear_search') }}</button>
                </div>
            </header>

            <div class="aho-quality-panel__meta">
                <span data-quality-count>
                    {{ trans_choice('aho.quality.visible_results', $issues->count(), ['count' => $issues->count()]) }}
                </span>
                <span>{{ __('aho.quality.table_hint') }}</span>
            </div>

            <div class="aho-quality-table-wrap">
                <table class="aho-quality-table" id="quality-table">
                    <thead>
                        <tr>
                            <th>{{ __('aho.fields.id') }}</th>
                            <th>{{ __('aho.fields.severity') }}</th>
                            <th>{{ __('aho.quality.rule') }}</th>
                            <th>{{ __('aho.fields.indicator') }}</th>
                            <th>{{ __('aho.fields.location') }}</th>
                            <th>{{ __('aho.fields.period') }}</th>
                            <th>{{ __('aho.fields.value') }}</th>
                            <th>{{ __('aho.fields.message') }}</th>
                            <th>{{ __('aho.actions.correct') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($issues as $issue)
                            <tr
                                class="quality-row"
                                data-severity="{{ $issue['severity'] }}"
                                data-quality-text="{{ \Illuminate\Support\Str::lower(trim(implode(' ', [
                                    $issue['fact_id'],
                                    $issue['severity'],
                                    $issue['rule'],
                                    $issue['indicator'],
                                    $issue['location'],
                                    $issue['period'],
                                    $issue['value'],
                                    $issue['message'],
                                ]))) }}"
                            >
                                <td class="aho-quality-table__id">
                                    <a href="{{ $issue['edit_url'] }}" class="aho-quality-link">#{{ $issue['fact_id'] }}</a>
                                </td>
                                <td>
                                    <span class="aho-quality-badge aho-quality-badge--{{ $issue['severity'] }}">
                                        {{ __("aho.quality.{$issue['severity']}") }}
                                    </span>
                                </td>
                                <td class="aho-quality-table__rule">{{ __("aho.quality.rules.{$issue['rule']}") }}</td>
                                <td class="aho-quality-table__indicator">{{ $issue['indicator'] }}</td>
                                <td>{{ $issue['location'] }}</td>
                                <td>{{ $issue['period'] }}</td>
                                <td>{{ $issue['value'] }}</td>
                                <td class="aho-quality-table__message">{{ $issue['message'] }}</td>
                                <td>
                                    <a href="{{ $issue['edit_url'] }}" class="aho-quality-link">
                                        {{ __('aho.actions.correct') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="aho-quality-empty">{{ __('aho.quality.no_issues') }}</td>
                            </tr>
                        @endforelse

                        <tr class="aho-quality-empty" data-quality-empty hidden>
                            <td colspan="9">{{ __('aho.quality.no_filtered_issues') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const bootQualityPage = () => {
                const page = document.querySelector('[data-quality-page]');

                if (!page || page.dataset.ready === '1') {
                    return;
                }

                page.dataset.ready = '1';

                const searchInput = page.querySelector('[data-quality-search]');
                const clearButton = page.querySelector('[data-quality-clear]');
                const countLabel = page.querySelector('[data-quality-count]');
                const emptyRow = page.querySelector('[data-quality-empty]');
                const filterButtons = Array.from(page.querySelectorAll('[data-quality-filter]'));
                const rows = Array.from(page.querySelectorAll('.quality-row'));
                let activeSeverity = 'all';

                const applyFilters = () => {
                    const term = (searchInput?.value || '').trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const matchesSeverity = activeSeverity === 'all' || row.dataset.severity === activeSeverity;
                        const matchesSearch = term === '' || (row.dataset.qualityText || row.textContent.toLowerCase()).includes(term);
                        const shouldShow = matchesSeverity && matchesSearch;

                        row.hidden = !shouldShow;

                        if (shouldShow) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyRow) {
                        emptyRow.hidden = visibleCount !== 0 || rows.length === 0;
                    }

                    if (countLabel) {
                        const singular = @js(__('aho.quality.visible_result'));
                        const plural = @js(__('aho.quality.visible_results_short'));
                        countLabel.textContent = visibleCount === 1
                            ? singular.replace(':count', visibleCount)
                            : plural.replace(':count', visibleCount);
                    }
                };

                filterButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        activeSeverity = button.dataset.qualityFilter || 'all';
                        filterButtons.forEach((candidate) => candidate.classList.toggle('is-active', candidate === button));
                        applyFilters();
                    });
                });

                searchInput?.addEventListener('input', applyFilters);

                clearButton?.addEventListener('click', () => {
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.focus();
                    }

                    applyFilters();
                });

                applyFilters();
            };

            document.addEventListener('DOMContentLoaded', bootQualityPage);
            document.addEventListener('livewire:navigated', bootQualityPage);
            bootQualityPage();
        })();
    </script>
</x-filament-panels::page>
