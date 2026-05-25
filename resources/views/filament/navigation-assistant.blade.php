@if (auth()->check())
    @php
        $country = trim((string) (request()->route('country') ?: request()->segment(2) ?: 'global')) ?: 'global';
        $adminUrl = fn (string $path = ''): string => url('/admin/'.$country.($path === '' ? '' : '/'.ltrim($path, '/')));
        $goTo = fn (string $menu): string => __('aho.assistant.go_to', ['menu' => $menu]);

        $items = [
            [
                'title' => __('Dashboard'),
                'url' => $adminUrl(),
                'answer' => $goTo(__('Dashboard')),
                'keywords' => ['dashboard', 'tableau de bord', 'accueil', 'statistiques', 'stats', 'graphique', 'chart'],
            ],
            [
                'title' => __('aho.resources.indicator_values.navigation'),
                'url' => $adminUrl('indicators/values'),
                'answer' => $goTo(__('aho.resources.indicator_values.navigation')),
                'keywords' => ['indicator', 'indicateur', 'valeur', 'values', 'pending', 'approved', 'rejected', 'validation', 'approve', 'csv', 'excel', 'export'],
            ],
            [
                'title' => __('aho.actions.add_indicator_value'),
                'url' => $adminUrl('indicators/values/create'),
                'answer' => $goTo(__('aho.actions.add_indicator_value')),
                'keywords' => ['create indicator value', 'nouvelle valeur', 'ajouter valeur', 'saisie indicateur', 'new value'],
            ],
            [
                'title' => __('aho.resources.indicators.navigation'),
                'url' => $adminUrl('indicators/definitions'),
                'answer' => $goTo(__('aho.resources.indicators.navigation')),
                'keywords' => ['indicator definition', 'definition indicateur', 'metadata', 'metadonnees', 'code indicateur', 'afrocode'],
            ],
            [
                'title' => __('aho.resources.indicator_archives.navigation'),
                'url' => $adminUrl('indicators/archives'),
                'answer' => $goTo(__('aho.resources.indicator_archives.navigation')),
                'keywords' => ['archive', 'archives', 'fact_data_archive', 'historique', 'old values'],
            ],
            [
                'title' => __('aho.resources.imports.navigation'),
                'url' => $adminUrl('indicators/imports'),
                'answer' => $goTo(__('aho.resources.imports.navigation')),
                'keywords' => ['import', 'imports', 'data wizard', 'assistant donnees', 'upload', 'chargement'],
            ],
            [
                'title' => __('aho.resources.exports.navigation'),
                'url' => $adminUrl('indicators/exports'),
                'answer' => $goTo(__('aho.resources.exports.navigation')),
                'keywords' => ['export', 'exports', 'csv', 'excel', 'download', 'telecharger'],
            ],
            [
                'title' => __('aho.resources.data_integration_connections.navigation'),
                'url' => $adminUrl('data-integration/connections'),
                'answer' => $goTo(__('aho.resources.data_integration_connections.navigation')),
                'keywords' => ['data integration', 'integration', 'dhis2', 'databank', 'who datahub', 'api', 'mapping', 'correspondance', 'connexion', 'server', 'username'],
            ],
            [
                'title' => __('aho.resources.indicator_quality_checks.navigation'),
                'url' => $adminUrl('data-quality/indicator-checks'),
                'answer' => $goTo(__('aho.resources.indicator_quality_checks.navigation')),
                'keywords' => ['data quality', 'qualite', 'quality', 'dqa', 'controle', 'alert', 'missing', 'consistency'],
            ],
            [
                'title' => __('aho.resources.locations.navigation'),
                'url' => $adminUrl('regions/locations'),
                'answer' => $goTo(__('aho.resources.locations.navigation')),
                'keywords' => ['country', 'countries', 'pays', 'location', 'localisation', 'region', 'iso'],
            ],
            [
                'title' => __('aho.resources.health_facilities.navigation'),
                'url' => $adminUrl('facilities/facilities'),
                'answer' => $goTo(__('aho.resources.health_facilities.navigation')),
                'keywords' => ['facility', 'facilities', 'formation sanitaire', 'fosa', 'service availability', 'service capacity', 'service readiness'],
            ],
            [
                'title' => __('aho.resources.health_workforce_values.navigation'),
                'url' => $adminUrl('health-workforce/values'),
                'answer' => $goTo(__('aho.resources.health_workforce_values.navigation')),
                'keywords' => ['workforce', 'personnel', 'cadre', 'health workforce', 'training institution', 'ressources humaines'],
            ],
            [
                'title' => __('aho.resources.health_service_values.navigation'),
                'url' => $adminUrl('health-services/values'),
                'answer' => $goTo(__('aho.resources.health_service_values.navigation')),
                'keywords' => ['health service', 'service sante', 'services', 'service values'],
            ],
            [
                'title' => __('aho.resources.data_element_values.navigation'),
                'url' => $adminUrl('data-elements/values'),
                'answer' => $goTo(__('aho.resources.data_element_values.navigation')),
                'keywords' => ['data element', 'element de donnees', 'elements', 'data values'],
            ],
            [
                'title' => __('aho.resources.knowledge_products.navigation'),
                'url' => $adminUrl('publications/products'),
                'answer' => $goTo(__('aho.resources.knowledge_products.navigation')),
                'keywords' => ['knowledge', 'publication', 'publications', 'produit de connaissance', 'document', 'file', 'pdf', 'resource'],
            ],
            [
                'title' => __('aho.resources.users.navigation'),
                'url' => $adminUrl('authentication/users'),
                'answer' => $goTo(__('aho.resources.users.navigation')),
                'keywords' => ['user', 'users', 'utilisateur', 'utilisateurs', 'account', 'compte', 'super admin', 'country admin'],
            ],
            [
                'title' => __('aho.resources.roles.navigation'),
                'url' => $adminUrl('authentication/roles'),
                'answer' => $goTo(__('aho.resources.roles.navigation')),
                'keywords' => ['role', 'roles', 'permission', 'permissions', 'access', 'acces', 'auth', 'authentication'],
            ],
            [
                'title' => __('aho.resources.api_tokens.navigation'),
                'url' => $adminUrl('api-tokens/status'),
                'answer' => $goTo(__('aho.resources.api_tokens.navigation')),
                'keywords' => ['api token', 'token', 'api', 'endpoint', 'integration api'],
            ],
            [
                'title' => __('aho.menus.uhc_clock'),
                'url' => $adminUrl('uhc-clock'),
                'answer' => $goTo(__('aho.menus.uhc_clock')),
                'keywords' => ['uhc', 'csu', 'clock', 'horloge', 'priority indicator'],
            ],
        ];

        $copy = [
            'intro' => __('aho.assistant.intro'),
            'empty' => __('aho.assistant.empty'),
            'fallback' => __('aho.assistant.fallback'),
            'suggestionIntro' => __('aho.assistant.suggestion_intro'),
            'suggestionsIntro' => __('aho.assistant.suggestions_intro'),
            'openLink' => __('aho.assistant.open_link'),
            'thinking' => __('aho.assistant.thinking'),
        ];
    @endphp

    <div class="aho-ai" data-aho-assistant>
        <button class="aho-ai__toggle" type="button" aria-expanded="false" aria-controls="aho-ai-panel" aria-label="{{ __('aho.assistant.open') }}">
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedChatBubbleLeftRight, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__toggle-icon'])) }}
            <span class="aho-ai__toggle-label">{{ __('aho.assistant.short') }}</span>
        </button>

        <section class="aho-ai__panel" id="aho-ai-panel" aria-label="{{ __('aho.assistant.title') }}" hidden>
            <header class="aho-ai__header">
                <div>
                    <strong>{{ __('aho.assistant.title') }}</strong>
                    <span>{{ __('aho.assistant.status') }}</span>
                </div>
                <button class="aho-ai__close" type="button" aria-label="{{ __('aho.assistant.close') }}">
                    {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedXMark, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__close-icon'])) }}
                </button>
            </header>

            <div class="aho-ai__messages" data-aho-ai-messages></div>

            <form class="aho-ai__form" data-aho-ai-form>
                <label class="sr-only" for="aho-ai-question">{{ __('aho.assistant.placeholder') }}</label>
                <input id="aho-ai-question" name="question" type="search" autocomplete="off" placeholder="{{ __('aho.assistant.placeholder') }}" data-aho-ai-input>
                <button type="submit" aria-label="{{ __('aho.assistant.send') }}">
                    {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedPaperAirplane, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__send-icon'])) }}
                </button>
            </form>
        </section>

        <script type="application/json" data-aho-ai-items>@json($items)</script>
        <script type="application/json" data-aho-ai-copy>@json($copy)</script>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-aho-assistant]');

            if (! root || root.dataset.ready === 'true') {
                return;
            }

            root.dataset.ready = 'true';

            const items = JSON.parse(root.querySelector('[data-aho-ai-items]').textContent || '[]');
            const copy = JSON.parse(root.querySelector('[data-aho-ai-copy]').textContent || '{}');
            const toggle = root.querySelector('.aho-ai__toggle');
            const close = root.querySelector('.aho-ai__close');
            const panel = root.querySelector('.aho-ai__panel');
            const messages = root.querySelector('[data-aho-ai-messages]');
            const form = root.querySelector('[data-aho-ai-form]');
            const input = root.querySelector('[data-aho-ai-input]');

            const normalize = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();

            const setOpen = (open) => {
                panel.hidden = ! open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                root.classList.toggle('is-open', open);
                localStorage.setItem('ahoNavigationAssistantOpen', open ? '1' : '0');

                if (open) {
                    window.setTimeout(() => input.focus(), 80);
                }
            };

            const appendMessage = (role, text, matches = []) => {
                const message = document.createElement('article');
                message.className = `aho-ai-message aho-ai-message--${role}`;

                const paragraph = document.createElement('p');
                paragraph.textContent = text;
                message.appendChild(paragraph);

                if (matches.length) {
                    const list = document.createElement('div');
                    list.className = 'aho-ai-message__links';

                    matches.forEach((match) => {
                        const link = document.createElement('a');
                        link.href = match.url;
                        link.textContent = `${copy.openLink}: ${match.title}`;
                        list.appendChild(link);
                    });

                    message.appendChild(list);
                }

                messages.appendChild(message);
                messages.scrollTop = messages.scrollHeight;
            };

            const findMatches = (question) => {
                const normalizedQuestion = normalize(question);
                const terms = normalizedQuestion
                    .split(/[^a-z0-9]+/)
                    .filter((term) => term.length > 2);

                return items
                    .map((item) => {
                        const haystack = normalize([
                            item.title,
                            item.answer,
                            ...(item.keywords || []),
                        ].join(' '));

                        let score = 0;

                        terms.forEach((term) => {
                            if (haystack.includes(term)) {
                                score += term.length > 5 ? 3 : 2;
                            }
                        });

                        if (normalizedQuestion && normalize(item.title).includes(normalizedQuestion)) {
                            score += 8;
                        }

                        return { ...item, score };
                    })
                    .filter((item) => item.score > 0)
                    .sort((a, b) => b.score - a.score)
                    .slice(0, 3);
            };

            const answer = (question) => {
                const matches = findMatches(question);

                if (! matches.length) {
                    appendMessage('assistant', copy.fallback);
                    return;
                }

                const intro = matches.length === 1 ? copy.suggestionIntro : copy.suggestionsIntro;
                appendMessage('assistant', `${intro} ${matches[0].answer}`, matches);
            };

            toggle.addEventListener('click', () => setOpen(panel.hidden));
            close.addEventListener('click', () => setOpen(false));
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const question = input.value.trim();

                if (! question) {
                    appendMessage('assistant', copy.empty);
                    return;
                }

                appendMessage('user', question);
                input.value = '';
                window.setTimeout(() => answer(question), 120);
            });

            appendMessage('assistant', copy.intro);
            setOpen(localStorage.getItem('ahoNavigationAssistantOpen') === '1');
        })();
    </script>
@endif
