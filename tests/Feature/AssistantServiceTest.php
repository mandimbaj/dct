<?php

namespace Tests\Feature;

use App\Services\AssistantService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantServiceTest extends TestCase
{
    public function test_it_sends_the_latest_question_once_with_precise_observatory_context(): void
    {
        config([
            'services.groq.key' => 'test-groq-key',
            'services.groq.model' => 'llama-3.3-70b-versatile',
        ]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'answer' => 'Un observatoire transforme les données de santé en informations utiles à la décision.',
                            'links' => [],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ]),
        ]);

        $question = 'À quoi sert un observatoire de la santé ?';
        $result = app(AssistantService::class)->answer($question, [
            ['role' => 'assistant', 'content' => 'Bienvenue dans le chatbot.'],
            ['role' => 'user', 'content' => $question],
        ], 'sn');

        $this->assertStringContainsString('informations utiles', $result['answer']);
        $this->assertSame([], $result['links']);

        Http::assertSent(function ($request) use ($question): bool {
            $messages = $request->data()['messages'];
            $userQuestions = array_values(array_filter(
                $messages,
                fn (array $message): bool => $message['role'] === 'user' && $message['content'] === $question,
            ));
            $systemPrompt = $messages[0]['content'];

            return count($userQuestions) === 1
                && str_contains($systemPrompt, 'Identify the user\'s exact intent')
                && str_contains($systemPrompt, '/admin/sn/national-observatory/national-observatories')
                && str_contains($systemPrompt, 'Never claim that the menu does not exist')
                && $request->data()['temperature'] === 0.2
                && $request->data()['response_format'] === ['type' => 'json_object'];
        });
    }

    public function test_it_keeps_only_known_internal_application_links(): void
    {
        config(['services.groq.key' => 'test-groq-key']);
        $observatoryUrl = url('/admin/sn/national-observatory/national-observatories');

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'answer' => 'Ouvrez la section des observatoires nationaux.',
                            'links' => [
                                ['title' => 'Lien externe', 'url' => 'https://example.com/phishing'],
                                ['title' => 'Titre inventé', 'url' => $observatoryUrl],
                            ],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ]),
        ]);

        $result = app(AssistantService::class)->answer(
            'Comment configurer un observatoire national ?',
            [],
            'sn',
        );

        $this->assertSame([[
            'title' => __('aho.resources.national_observatories.navigation'),
            'url' => $observatoryUrl,
        ]], $result['links']);
    }
}
