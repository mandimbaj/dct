<x-filament-panels::page>
    @php
        $tokens = $this->tokens();
        $endpoints = collect($this->endpoints());

        $activeCount = $tokens->filter->isActive()->count();
        $revokedCount = $tokens->whereNotNull('revoked_at')->count();
        $expiredCount = $tokens
            ->filter(fn ($token): bool => blank($token->revoked_at) && filled($token->expires_at) && $token->expires_at->isPast())
            ->count();

        $readCount = $endpoints->where('group', 'read')->count();
        $writeCount = $endpoints->where('group', 'write')->count();
    @endphp

    <div class="aho-api" data-api-page>
        <section class="aho-api__hero">
            <div>
                <p class="aho-api__eyebrow">{{ __('aho.menus.api_tokens') }}</p>
                <h2 class="aho-api__title">{{ __('aho.api_tokens.title') }}</h2>
                <p class="aho-api__subtitle">{{ __('aho.api_tokens.description') }}</p>
            </div>

            <div class="aho-api__summary" aria-label="{{ __('aho.api_tokens.summary') }}">
                <span>{{ __('aho.api_tokens.active_tokens') }}</span>
                <strong>{{ number_format($activeCount) }}</strong>
            </div>
        </section>

        <section class="aho-api-create">
            <div class="aho-api-create__text">
                <h3>{{ __('aho.api_tokens.create_title') }}</h3>
                <p>{{ __('aho.api_tokens.create_hint') }}</p>
            </div>

            <form wire:submit.prevent="createToken" class="aho-api-form">
                <label>
                    <span>{{ __('aho.api_tokens.token_name') }}</span>
                    <input wire:model="tokenName" type="text">
                </label>

                <label>
                    <span>{{ __('aho.api_tokens.expires_at') }}</span>
                    <input wire:model="expiresAt" type="date">
                </label>

                <button type="submit">{{ __('aho.api_tokens.create') }}</button>
            </form>

            @if ($plainToken)
                <div class="aho-api-token-copy">
                    <div>
                        <strong>{{ __('aho.api_tokens.copy_once') }}</strong>
                        <p>{{ __('aho.api_tokens.copy_hint') }}</p>
                    </div>
                    <code>{{ $plainToken }}</code>
                    <button type="button" data-copy-value="{{ $plainToken }}">
                        {{ __('aho.api_tokens.copy') }}
                    </button>
                </div>
            @endif
        </section>

        <section class="aho-api-panel">
            <div class="aho-api-panel__header">
                <div>
                    <h3>{{ __('aho.api_tokens.active_tokens') }}</h3>
                    <p>{{ __('aho.api_tokens.tokens_hint') }}</p>
                </div>

                <div class="aho-api-search">
                    <input
                        type="search"
                        placeholder="{{ __('aho.api_tokens.search_tokens') }}"
                        data-api-token-search
                    >
                    <button type="button" data-api-token-clear>{{ __('aho.api_tokens.clear') }}</button>
                </div>
            </div>

            <div class="aho-api-stats" aria-label="{{ __('aho.api_tokens.token_filters') }}">
                <button type="button" class="aho-api-stat is-active" data-api-token-filter="all">
                    <span>{{ __('aho.api_tokens.all_tokens') }}</span>
                    <strong>{{ number_format($tokens->count()) }}</strong>
                </button>
                <button type="button" class="aho-api-stat aho-api-stat--active" data-api-token-filter="active">
                    <span>{{ __('aho.api_tokens.active_label') }}</span>
                    <strong>{{ number_format($activeCount) }}</strong>
                </button>
                <button type="button" class="aho-api-stat aho-api-stat--expired" data-api-token-filter="expired">
                    <span>{{ __('aho.api_tokens.expired_label') }}</span>
                    <strong>{{ number_format($expiredCount) }}</strong>
                </button>
                <button type="button" class="aho-api-stat aho-api-stat--revoked" data-api-token-filter="revoked">
                    <span>{{ __('aho.api_tokens.revoked_label') }}</span>
                    <strong>{{ number_format($revokedCount) }}</strong>
                </button>
            </div>

            <div class="aho-api-panel__meta">
                <span data-api-token-count>{{ trans_choice('aho.api_tokens.visible_tokens', $tokens->count(), ['count' => number_format($tokens->count())]) }}</span>
                <span>{{ __('aho.api_tokens.safety_hint') }}</span>
            </div>

            <div class="aho-api-table-wrap">
                <table class="aho-api-table">
                    <thead>
                        <tr>
                            <th>{{ __('aho.api_tokens.token_name') }}</th>
                            <th>{{ __('aho.api_tokens.status_column') }}</th>
                            <th>{{ __('aho.api_tokens.owner') }}</th>
                            <th>{{ __('aho.api_tokens.prefix') }}</th>
                            <th>{{ __('aho.api_tokens.last_used') }}</th>
                            <th>{{ __('aho.api_tokens.expires_at') }}</th>
                            <th class="aho-api-table__actions">{{ __('aho.api_tokens.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tokens as $token)
                            @php
                                $status = $token->revoked_at
                                    ? 'revoked'
                                    : ($token->expires_at?->isPast() ? 'expired' : 'active');
                                $tokenText = implode(' ', [
                                    $token->name,
                                    $token->user?->email,
                                    $token->token_prefix,
                                    $status,
                                ]);
                            @endphp
                            <tr
                                data-api-token-row
                                data-token-status="{{ $status }}"
                                data-token-text="{{ \Illuminate\Support\Str::lower($tokenText) }}"
                            >
                                <td class="aho-api-table__name">{{ $token->name }}</td>
                                <td>
                                    <span class="aho-api-badge aho-api-badge--{{ $status }}">
                                        {{ __("aho.api_tokens.{$status}_label") }}
                                    </span>
                                </td>
                                <td>{{ $token->user?->email ?? '-' }}</td>
                                <td><code>{{ $token->token_prefix }}...</code></td>
                                <td>{{ $token->last_used_at?->diffForHumans() ?? '-' }}</td>
                                <td>{{ $token->expires_at?->toDateString() ?? __('aho.api_tokens.never') }}</td>
                                <td class="aho-api-table__actions">
                                    @if ($token->isActive())
                                        <button type="button" wire:click="revokeToken({{ $token->id }})" class="aho-api-danger">
                                            {{ __('aho.api_tokens.revoke') }}
                                        </button>
                                    @else
                                        <span class="aho-api-muted">{{ __('aho.api_tokens.no_action') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="aho-api-empty">
                                    {{ __('aho.api_tokens.no_tokens') }}
                                </td>
                            </tr>
                        @endforelse

                        <tr class="aho-api-empty-row" data-api-token-empty hidden>
                            <td colspan="7" class="aho-api-empty">{{ __('aho.api_tokens.no_filtered_tokens') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="aho-api-panel">
            <div class="aho-api-panel__header">
                <div>
                    <h3>{{ __('aho.api_tokens.available_links') }}</h3>
                    <p>{{ __('aho.api_tokens.endpoints_hint') }}</p>
                </div>

                <div class="aho-api-search">
                    <input
                        type="search"
                        placeholder="{{ __('aho.api_tokens.search_endpoints') }}"
                        data-api-endpoint-search
                    >
                    <button type="button" data-api-endpoint-clear>{{ __('aho.api_tokens.clear') }}</button>
                </div>
            </div>

            <div class="aho-api-stats" aria-label="{{ __('aho.api_tokens.endpoint_filters') }}">
                <button type="button" class="aho-api-stat is-active" data-api-endpoint-filter="all">
                    <span>{{ __('aho.api_tokens.all_endpoints') }}</span>
                    <strong>{{ number_format($endpoints->count()) }}</strong>
                </button>
                <button type="button" class="aho-api-stat" data-api-endpoint-filter="read">
                    <span>{{ __('aho.api_tokens.read_endpoints') }}</span>
                    <strong>{{ number_format($readCount) }}</strong>
                </button>
                <button type="button" class="aho-api-stat aho-api-stat--write" data-api-endpoint-filter="write">
                    <span>{{ __('aho.api_tokens.write_endpoints') }}</span>
                    <strong>{{ number_format($writeCount) }}</strong>
                </button>
            </div>

            <div class="aho-api-panel__meta">
                <span data-api-endpoint-count>{{ trans_choice('aho.api_tokens.visible_endpoints', $endpoints->count(), ['count' => number_format($endpoints->count())]) }}</span>
                <span>{{ __('aho.api_tokens.authorization_hint') }}</span>
            </div>

            <div class="aho-api-table-wrap">
                <table class="aho-api-table aho-api-table--endpoints">
                    <thead>
                        <tr>
                            <th>{{ __('aho.api_tokens.method') }}</th>
                            <th>{{ __('aho.api_tokens.url') }}</th>
                            <th>{{ __('aho.api_tokens.description_column') }}</th>
                            <th class="aho-api-table__actions">{{ __('aho.api_tokens.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($endpoints as $endpoint)
                            <tr
                                data-api-endpoint-row
                                data-endpoint-group="{{ $endpoint['group'] }}"
                                data-endpoint-text="{{ \Illuminate\Support\Str::lower($endpoint['method'].' '.$endpoint['url'].' '.$endpoint['description']) }}"
                            >
                                <td>
                                    <span class="aho-api-method aho-api-method--{{ strtolower($endpoint['method']) }}">
                                        {{ $endpoint['method'] }}
                                    </span>
                                </td>
                                <td><code>{{ $endpoint['url'] }}</code></td>
                                <td>{{ $endpoint['description'] }}</td>
                                <td class="aho-api-table__actions">
                                    <button type="button" class="aho-api-copy" data-copy-value="{{ $endpoint['url'] }}">
                                        {{ __('aho.api_tokens.copy') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        <tr class="aho-api-empty-row" data-api-endpoint-empty hidden>
                            <td colspan="4" class="aho-api-empty">{{ __('aho.api_tokens.no_filtered_endpoints') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="aho-api-examples">
            <div class="aho-api-examples__header">
                <h3>{{ __('aho.api_tokens.example') }}</h3>
                <p>{{ __('aho.api_tokens.examples_hint') }}</p>
            </div>

            <div class="aho-api-example-grid">
                <article>
                    <h4>{{ __('aho.api_tokens.get_example') }}</h4>
                    <pre><code>curl -H "Authorization: Bearer YOUR_TOKEN" \
    {{ url('/api/v1/indicator-values?per_page=25') }}</code></pre>
                </article>

                <article>
                    <h4>{{ __('aho.api_tokens.post_example') }}</h4>
                    <pre><code>curl -X POST {{ url('/api/v1/indicator-values') }} \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{{ $this->examplePayload() }}'</code></pre>
                </article>
            </div>
        </section>
    </div>

    <script>
        (() => {
            if (window.__ahoApiTokensReady) {
                return;
            }

            window.__ahoApiTokensReady = true;

            const labels = {
                visibleTokens: @js(__('aho.api_tokens.visible_tokens_short')),
                visibleEndpoints: @js(__('aho.api_tokens.visible_endpoints_short')),
                copied: @js(__('aho.api_tokens.copied')),
                copy: @js(__('aho.api_tokens.copy')),
            };

            const normalize = (value) => (value || '').toString().toLocaleLowerCase();

            const updateTokenRows = (page) => {
                const activeFilter = page.querySelector('[data-api-token-filter].is-active')?.dataset.apiTokenFilter || 'all';
                const query = normalize(page.querySelector('[data-api-token-search]')?.value);
                const rows = Array.from(page.querySelectorAll('[data-api-token-row]'));
                let visible = 0;

                rows.forEach((row) => {
                    const statusMatches = activeFilter === 'all' || row.dataset.tokenStatus === activeFilter;
                    const queryMatches = !query || normalize(row.dataset.tokenText).includes(query);
                    const shouldShow = statusMatches && queryMatches;

                    row.hidden = !shouldShow;
                    if (shouldShow) {
                        visible += 1;
                    }
                });

                const empty = page.querySelector('[data-api-token-empty]');
                if (empty) {
                    empty.hidden = rows.length === 0 || visible > 0;
                }

                const counter = page.querySelector('[data-api-token-count]');
                if (counter) {
                    counter.textContent = labels.visibleTokens.replace(':count', visible.toLocaleString());
                }
            };

            const updateEndpointRows = (page) => {
                const activeFilter = page.querySelector('[data-api-endpoint-filter].is-active')?.dataset.apiEndpointFilter || 'all';
                const query = normalize(page.querySelector('[data-api-endpoint-search]')?.value);
                const rows = Array.from(page.querySelectorAll('[data-api-endpoint-row]'));
                let visible = 0;

                rows.forEach((row) => {
                    const groupMatches = activeFilter === 'all' || row.dataset.endpointGroup === activeFilter;
                    const queryMatches = !query || normalize(row.dataset.endpointText).includes(query);
                    const shouldShow = groupMatches && queryMatches;

                    row.hidden = !shouldShow;
                    if (shouldShow) {
                        visible += 1;
                    }
                });

                const empty = page.querySelector('[data-api-endpoint-empty]');
                if (empty) {
                    empty.hidden = rows.length === 0 || visible > 0;
                }

                const counter = page.querySelector('[data-api-endpoint-count]');
                if (counter) {
                    counter.textContent = labels.visibleEndpoints.replace(':count', visible.toLocaleString());
                }
            };

            const copyValue = async (button) => {
                const value = button.dataset.copyValue || '';

                try {
                    await navigator.clipboard.writeText(value);
                    button.textContent = labels.copied;
                    setTimeout(() => {
                        button.textContent = labels.copy;
                    }, 1600);
                } catch (error) {
                    const textarea = document.createElement('textarea');
                    textarea.value = value;
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    textarea.remove();
                    button.textContent = labels.copied;
                    setTimeout(() => {
                        button.textContent = labels.copy;
                    }, 1600);
                }
            };

            document.addEventListener('click', (event) => {
                const tokenFilter = event.target.closest('[data-api-token-filter]');
                if (tokenFilter) {
                    const page = tokenFilter.closest('[data-api-page]');
                    page.querySelectorAll('[data-api-token-filter]').forEach((button) => button.classList.remove('is-active'));
                    tokenFilter.classList.add('is-active');
                    updateTokenRows(page);
                    return;
                }

                const endpointFilter = event.target.closest('[data-api-endpoint-filter]');
                if (endpointFilter) {
                    const page = endpointFilter.closest('[data-api-page]');
                    page.querySelectorAll('[data-api-endpoint-filter]').forEach((button) => button.classList.remove('is-active'));
                    endpointFilter.classList.add('is-active');
                    updateEndpointRows(page);
                    return;
                }

                const tokenClear = event.target.closest('[data-api-token-clear]');
                if (tokenClear) {
                    const page = tokenClear.closest('[data-api-page]');
                    page.querySelector('[data-api-token-search]').value = '';
                    updateTokenRows(page);
                    return;
                }

                const endpointClear = event.target.closest('[data-api-endpoint-clear]');
                if (endpointClear) {
                    const page = endpointClear.closest('[data-api-page]');
                    page.querySelector('[data-api-endpoint-search]').value = '';
                    updateEndpointRows(page);
                    return;
                }

                const copyButton = event.target.closest('[data-copy-value]');
                if (copyButton) {
                    copyValue(copyButton);
                }
            });

            document.addEventListener('input', (event) => {
                const tokenSearch = event.target.closest('[data-api-token-search]');
                if (tokenSearch) {
                    updateTokenRows(tokenSearch.closest('[data-api-page]'));
                    return;
                }

                const endpointSearch = event.target.closest('[data-api-endpoint-search]');
                if (endpointSearch) {
                    updateEndpointRows(endpointSearch.closest('[data-api-page]'));
                }
            });

            const init = () => {
                document.querySelectorAll('[data-api-page]').forEach((page) => {
                    updateTokenRows(page);
                    updateEndpointRows(page);
                });
            };

            document.addEventListener('DOMContentLoaded', init);
            document.addEventListener('livewire:navigated', init);
            document.addEventListener('livewire:morph.updated', init);
            init();
        })();
    </script>
</x-filament-panels::page>
