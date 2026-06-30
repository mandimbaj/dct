<div class="aho-ai__livewire">
    <div class="aho-ai__toolbar">
        <button class="aho-ai__clear" type="button"
            wire:click="clearConversation"
            title="{{ __('aho.assistant.clear') }}"
            aria-label="{{ __('aho.assistant.clear') }}">
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedArrowPath, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__action-icon'])) }}
            <span>{{ __('aho.assistant.clear') }}</span>
        </button>
    </div>

    <div class="aho-ai__messages" id="aho-ai-messages">
        @foreach ($messages as $message)
            <article class="aho-ai-message aho-ai-message--{{ $message['role'] }}">
                <p>{{ $message['content'] }}</p>
                @if (!empty($message['links']))
                    <div class="aho-ai-message__links">
                        @foreach ($message['links'] as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener">
                                {{ __('aho.assistant.open_link') }}: {{ $link['title'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach

        @if ($thinking)
            <article class="aho-ai-message aho-ai-message--assistant aho-ai-message--typing">
                <span class="aho-ai-dots"><span></span><span></span><span></span></span>
            </article>
        @endif
    </div>

    <form class="aho-ai__form" wire:submit="sendMessage">
        <label class="sr-only" for="aho-ai-question-lw">{{ __('aho.assistant.placeholder') }}</label>
        <input
            id="aho-ai-question-lw"
            type="search"
            autocomplete="off"
            placeholder="{{ __('aho.assistant.placeholder') }}"
            wire:model="input"
            wire:loading.attr="disabled"
        >
        <button type="submit" aria-label="{{ __('aho.assistant.send') }}" wire:loading.attr="disabled">
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedPaperAirplane, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['aho-ai__send-icon'])) }}
        </button>
    </form>
</div>

<script>
    document.addEventListener('livewire:updated', () => {
        const msgs = document.getElementById('aho-ai-messages');
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
    });
</script>
