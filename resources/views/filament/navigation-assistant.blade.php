@if (auth()->check())
    <div class="aho-ai" data-aho-assistant>
        <button class="aho-ai__toggle" type="button" aria-expanded="false" aria-controls="aho-ai-panel" aria-label="{{ __('aho.assistant.open') }}">
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedChatBubbleLeftRight, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__toggle-icon'])) }}
            <span class="aho-ai__toggle-label">{{ __('aho.assistant.short') }}</span>
        </button>

        <section class="aho-ai__panel" id="aho-ai-panel" aria-label="{{ __('aho.assistant.title') }}" hidden>
            <header class="aho-ai__header">
                <div>
                    <strong>{{ __('aho.assistant.title') }}</strong>
                    <span class="aho-ai__status">
                        <span class="aho-ai__status-dot"></span>
                        {{ __('aho.assistant.status') }}
                    </span>
                </div>
                <div class="aho-ai__header-actions">
                    <button class="aho-ai__close" type="button" aria-label="{{ __('aho.assistant.close') }}" data-aho-ai-close>
                        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedXMark, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__close-icon'])) }}
                    </button>
                </div>
            </header>

            @livewire('assistant-chat')
        </section>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-aho-assistant]');
            if (! root || root.dataset.ready === 'true') return;
            root.dataset.ready = 'true';

            const toggle = root.querySelector('.aho-ai__toggle');
            const close  = root.querySelector('[data-aho-ai-close]');
            const panel  = root.querySelector('.aho-ai__panel');

            const setOpen = (open) => {
                panel.hidden = ! open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                root.classList.toggle('is-open', open);
                localStorage.setItem('ahoNavigationAssistantOpen', open ? '1' : '0');
                if (open) {
                    window.setTimeout(() => {
                        const inp = panel.querySelector('input[type="search"]');
                        if (inp) inp.focus();
                    }, 80);
                }
            };

            toggle.addEventListener('click', () => setOpen(panel.hidden));
            close.addEventListener('click',  () => setOpen(false));

            setOpen(localStorage.getItem('ahoNavigationAssistantOpen') === '1');
        })();
    </script>
@endif
