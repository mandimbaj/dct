@if (auth()->check())
    @php
        $country = trim((string) (request()->route('country') ?: request()->segment(2) ?: 'af')) ?: 'af';
        $adminUrl = fn (string $path = ''): string => url('/admin/'.$country.($path === '' ? '' : '/'.ltrim($path, '/')));
        $goTo = fn (string $menu): string => __('aho.assistant.go_to', ['menu' => $menu]);

        $items = [
            [
                'title' => __('Dashboard'),
                'url' => $adminUrl(),
                'answer' => $goTo(__('Dashboard')),
                'intent' => 'dashboard',
                'keywords' => ['dashboard', 'tableau de bord', 'accueil', 'statistiques', 'stats', 'graphique', 'chart'],
            ],
            [
                'title' => __('aho.resources.indicator_values.navigation'),
                'url' => $adminUrl('indicators/values'),
                'answer' => __('aho.assistant.guides.indicator_value'),
                'guide' => __('aho.assistant.guides.indicator_value'),
                'intent' => 'indicator_value',
                'keywords' => ['indicator', 'indicateur', 'valeur', 'values', 'data entry', 'saisie', 'charger donnees indicateur', 'charger les donnees indicateur', 'chargement indicateur', 'pending', 'approved', 'rejected', 'validation', 'approve', 'csv', 'excel', 'export'],
            ],
            [
                'title' => __('aho.actions.add_indicator_value'),
                'url' => $adminUrl('indicators/values/create'),
                'answer' => __('aho.assistant.guides.indicator_value'),
                'guide' => __('aho.assistant.guides.indicator_value'),
                'intent' => 'indicator_value',
                'keywords' => ['create indicator value', 'nouvelle valeur', 'ajouter valeur', 'saisie indicateur', 'new value', 'comment charger les donnees indicateur', 'comment charger les données indicateur', 'ajouter donnee indicateur', 'valider donnee indicateur'],
            ],
            [
                'title' => __('aho.resources.indicators.navigation'),
                'url' => $adminUrl('indicators/definitions'),
                'answer' => $goTo(__('aho.resources.indicators.navigation')),
                'intent' => 'indicator_definition',
                'keywords' => ['indicator definition', 'definition indicateur', 'metadata', 'metadonnees', 'code indicateur', 'afrocode'],
            ],
            [
                'title' => __('aho.resources.indicator_archives.navigation'),
                'url' => $adminUrl('indicators/archives'),
                'answer' => $goTo(__('aho.resources.indicator_archives.navigation')),
                'intent' => 'archives',
                'keywords' => ['archive', 'archives', 'fact_data_archive', 'historique', 'old values'],
            ],
            [
                'title' => __('aho.resources.imports.navigation'),
                'url' => $adminUrl('indicators/imports'),
                'answer' => __('aho.assistant.guides.imports'),
                'guide' => __('aho.assistant.guides.imports'),
                'intent' => 'imports',
                'keywords' => ['import', 'imports', 'data wizard', 'assistant donnees', 'upload', 'chargement', 'fichier csv', 'csv import', 'televerser', 'téléverser'],
            ],
            [
                'title' => __('aho.resources.exports.navigation'),
                'url' => $adminUrl('indicators/exports'),
                'answer' => __('aho.assistant.guides.exports'),
                'guide' => __('aho.assistant.guides.exports'),
                'intent' => 'exports',
                'keywords' => ['export', 'exports', 'csv', 'excel', 'download', 'telecharger'],
            ],
            [
                'title' => __('aho.resources.data_integration_connections.navigation'),
                'url' => $adminUrl('data-integration/connections'),
                'answer' => __('aho.assistant.guides.data_integration'),
                'guide' => __('aho.assistant.guides.data_integration'),
                'intent' => 'data_integration',
                'keywords' => ['data integration', 'integration', 'dhis2', 'databank', 'who datahub', 'mapping', 'correspondance', 'connexion', 'server', 'username'],
            ],
            [
                'title' => __('aho.resources.indicator_quality_checks.navigation'),
                'url' => $adminUrl('data-quality/indicator-checks'),
                'answer' => __('aho.assistant.guides.data_quality'),
                'guide' => __('aho.assistant.guides.data_quality'),
                'intent' => 'data_quality',
                'keywords' => ['data quality', 'qualite', 'qualité', 'quality', 'dqa', 'controle', 'contrôle', 'alert', 'missing', 'consistency', 'coherence', 'cohérence'],
            ],
            [
                'title' => __('aho.resources.locations.navigation'),
                'url' => $adminUrl('regions/locations'),
                'answer' => $goTo(__('aho.resources.locations.navigation')),
                'intent' => 'locations',
                'keywords' => ['country', 'countries', 'pays', 'location', 'localisation', 'region', 'iso'],
            ],
            [
                'title' => __('aho.resources.health_facilities.navigation'),
                'url' => $adminUrl('facilities/facilities'),
                'answer' => $goTo(__('aho.resources.health_facilities.navigation')),
                'intent' => 'facilities',
                'keywords' => ['facility', 'facilities', 'formation sanitaire', 'fosa', 'service availability', 'service capacity', 'service readiness'],
            ],
            [
                'title' => __('aho.resources.health_workforce_values.navigation'),
                'url' => $adminUrl('health-workforce/values'),
                'answer' => $goTo(__('aho.resources.health_workforce_values.navigation')),
                'intent' => 'health_workforce',
                'keywords' => ['workforce', 'personnel', 'cadre', 'health workforce', 'training institution', 'ressources humaines'],
            ],
            [
                'title' => __('aho.resources.health_service_values.navigation'),
                'url' => $adminUrl('health-services/values'),
                'answer' => $goTo(__('aho.resources.health_service_values.navigation')),
                'intent' => 'health_services',
                'keywords' => ['health service', 'service sante', 'services', 'service values'],
            ],
            [
                'title' => __('aho.resources.data_element_values.navigation'),
                'url' => $adminUrl('data-elements/values'),
                'answer' => $goTo(__('aho.resources.data_element_values.navigation')),
                'intent' => 'data_elements',
                'keywords' => ['data element', 'element de donnees', 'elements', 'data values'],
            ],
            [
                'title' => __('aho.resources.knowledge_products.navigation'),
                'url' => $adminUrl('publications/products'),
                'answer' => $goTo(__('aho.resources.knowledge_products.navigation')),
                'intent' => 'publications',
                'keywords' => ['knowledge', 'publication', 'publications', 'produit de connaissance', 'document', 'file', 'pdf', 'resource'],
            ],
            [
                'title' => __('aho.resources.users.navigation'),
                'url' => $adminUrl('authentication/users'),
                'answer' => __('aho.assistant.guides.users'),
                'guide' => __('aho.assistant.guides.users'),
                'intent' => 'users',
                'keywords' => ['user', 'users', 'utilisateur', 'utilisateurs', 'account', 'compte', 'super admin', 'country admin'],
            ],
            [
                'title' => __('aho.resources.roles.navigation'),
                'url' => $adminUrl('authentication/roles'),
                'answer' => __('aho.assistant.guides.roles'),
                'guide' => __('aho.assistant.guides.roles'),
                'intent' => 'roles',
                'keywords' => ['role', 'roles', 'permission', 'permissions', 'access', 'acces', 'auth', 'authentication'],
            ],
            [
                'title' => __('aho.resources.api_tokens.navigation'),
                'url' => $adminUrl('api-tokens/status'),
                'answer' => __('aho.assistant.guides.api_tokens'),
                'guide' => __('aho.assistant.guides.api_tokens'),
                'intent' => 'api_tokens',
                'keywords' => ['api token', 'token', 'api', 'endpoint', 'integration api', 'jeton api', 'cle api', 'clé api', 'bearer', 'rest api'],
            ],
            [
                'title' => __('aho.menus.uhc_clock'),
                'url' => $adminUrl('uhc-clock'),
                'answer' => $goTo(__('aho.menus.uhc_clock')),
                'intent' => 'uhc_clock',
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

            const stopWords = new Set([
                'comment', 'pour', 'avec', 'dans', 'sur', 'des', 'les', 'une', 'aux', 'par', 'que', 'qui', 'quoi',
                'the', 'and', 'for', 'how', 'what', 'where', 'with', 'from', 'dans', 'faire', 'utiliser', 'use',
                'donnee', 'donnees', 'data', 'valeur', 'values', 'menu', 'page', 'aller', 'open', 'ouvrir',
            ]);

            const intentRules = [
                ['api_tokens', [' api ', 'api', 'token', 'jeton', 'endpoint', 'bearer', 'rest api', 'cle api', 'clef api']],
                ['imports', ['import', 'imports', 'importer', 'csv import', 'fichier csv', 'televerser', 'upload file']],
                ['exports', ['export', 'exports', 'exporter', 'excel', 'download', 'telecharger']],
                ['data_integration', ['data integration', 'integration', 'dhis2', 'databank', 'who datahub', 'mapping', 'connexion']],
                ['data_quality', ['data quality', 'qualite', 'quality', 'dqa', 'controle', 'coherence', 'consistency', 'validation rule']],
                ['users', ['user', 'users', 'utilisateur', 'utilisateurs', 'account', 'compte']],
                ['roles', ['role', 'roles', 'permission', 'permissions', 'access', 'acces']],
                ['indicator_value', ['valeur indicateur', 'indicator value', 'charger donnees indicateur', 'charger les donnees indicateur', 'saisie indicateur', 'ajouter donnee indicateur']],
                ['indicator_definition', ['definition indicateur', 'indicator definition', 'metadata', 'metadonnees', 'afrocode']],
                ['archives', ['archive', 'archives', 'historique', 'fact data archive', 'fact_data_archive']],
                ['publications', ['publication', 'publications', 'knowledge', 'produit de connaissance', 'document', 'pdf']],
                ['locations', ['pays', 'country', 'countries', 'location', 'localisation', 'iso']],
                ['facilities', ['facility', 'facilities', 'formation sanitaire', 'fosa']],
                ['health_workforce', ['workforce', 'personnel', 'ressources humaines', 'cadre']],
                ['health_services', ['health service', 'service sante', 'services de sante']],
                ['data_elements', ['data element', 'element de donnees']],
                ['uhc_clock', ['uhc', 'csu', 'clock', 'horloge']],
                ['dashboard', ['dashboard', 'tableau de bord', 'accueil', 'graphique']],
            ];

            const detectIntent = (normalizedQuestion) => {
                const paddedQuestion = ` ${normalizedQuestion} `;

                for (const [intent, patterns] of intentRules) {
                    if (patterns.some((pattern) => {
                        const normalizedPattern = normalize(pattern).trim();
                        const isShortWord = /^[a-z0-9]+$/.test(normalizedPattern) && normalizedPattern.length <= 4;

                        return isShortWord
                            ? paddedQuestion.includes(` ${normalizedPattern} `)
                            : normalizedQuestion.includes(normalizedPattern);
                    })) {
                        return intent;
                    }
                }

                return null;
            };

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
                const detectedIntent = detectIntent(normalizedQuestion);
                const terms = normalizedQuestion
                    .split(/[^a-z0-9]+/)
                    .filter((term) => term.length > 2 && ! stopWords.has(term));

                return items
                    .map((item) => {
                        const haystack = normalize([
                            item.title,
                            item.answer,
                            ...(item.keywords || []),
                        ].join(' '));

                        let score = 0;

                        if (detectedIntent && item.intent === detectedIntent) {
                            score += 48;
                        } else if (detectedIntent && item.guide) {
                            score -= 10;
                        }

                        (item.keywords || []).forEach((keyword) => {
                            const normalizedKeyword = normalize(keyword);

                            if (normalizedKeyword && normalizedQuestion.includes(normalizedKeyword)) {
                                score += normalizedKeyword.length > 12 ? 16 : 10;
                            }
                        });

                        terms.forEach((term) => {
                            if (haystack.includes(term)) {
                                score += term.length > 5 ? 3 : 2;
                            }
                        });

                        if (normalizedQuestion && normalize(item.title).includes(normalizedQuestion)) {
                            score += 8;
                        }

                        if (normalizedQuestion && haystack.includes(normalizedQuestion)) {
                            score += 12;
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
                const response = matches[0].guide || `${intro} ${matches[0].answer}`;
                const responseLinks = matches[0].guide
                    ? matches.filter((match) => match.guide === matches[0].guide).slice(0, 2)
                    : matches;

                appendMessage('assistant', response, responseLinks);
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
