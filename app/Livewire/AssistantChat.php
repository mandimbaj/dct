<?php

namespace App\Livewire;

use App\Services\AssistantService;
use Illuminate\View\View;
use Livewire\Component;

class AssistantChat extends Component
{
    public string $input = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public bool $thinking = false;

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'assistant',
            'content' => __('aho.assistant.intro'),
            'links' => [],
        ]];
    }

    public function sendMessage(): void
    {
        $question = mb_substr(trim($this->input), 0, 2000);

        if ($question === '' || $this->thinking) {
            return;
        }

        $this->input = '';
        $this->thinking = true;

        $this->messages[] = ['role' => 'user', 'content' => $question, 'links' => []];

        $answer = app(AssistantService::class)->answer(
            $question,
            $this->messages,
            $this->detectCountry(),
        );

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $answer['answer'],
            'links' => $answer['links'],
        ];

        $this->thinking = false;
    }

    public function clearConversation(): void
    {
        $this->messages = [[
            'role' => 'assistant',
            'content' => __('aho.assistant.intro'),
            'links' => [],
        ]];
        $this->thinking = false;
    }

    private function detectCountry(): string
    {
        $segment = request()->segment(2) ?: 'af';

        return preg_match('/^[a-z]{2}$/i', $segment) === 1 ? strtolower($segment) : 'af';
    }

    public function render(): View
    {
        return view('livewire.assistant-chat');
    }
}
