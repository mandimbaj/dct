<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    private const DEFAULT_MODEL = 'llama-3.3-70b-versatile';

    private const MAX_COMPLETION_TOKENS = 900;

    /**
     * @param  array<int, mixed>  $history
     * @return array{answer: string, links: array<int, array{title: string, url: string}>}
     */
    public function answer(string $question, array $history = [], string $country = 'af'): array
    {
        $question = mb_substr(trim($question), 0, 2000);
        $apiKey = trim((string) config('services.groq.key', ''));

        if ($question === '') {
            return ['answer' => __('aho.assistant.empty'), 'links' => []];
        }

        if ($apiKey === '') {
            return ['answer' => __('aho.assistant.configuration_error'), 'links' => []];
        }

        $country = $this->normalizeCountry($country);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(self::API_URL, [
                    'model' => (string) config('services.groq.model', self::DEFAULT_MODEL),
                    'temperature' => 0.2,
                    'max_completion_tokens' => self::MAX_COMPLETION_TOKENS,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => $this->buildMessages($history, $question, $country),
                ]);

            if (! $response->successful()) {
                Log::warning('Assistant Groq call failed.', [
                    'status' => $response->status(),
                    'request_id' => $response->header('x-request-id'),
                    'message' => mb_substr($response->body(), 0, 1000),
                ]);

                return ['answer' => __('aho.assistant.error_retry'), 'links' => []];
            }

            return $this->parseResponse(
                trim((string) ($response->json('choices.0.message.content') ?? '')),
                $country,
            );
        } catch (\Throwable $exception) {
            Log::warning('Assistant Groq exception.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return ['answer' => __('aho.assistant.error_retry'), 'links' => []];
        }
    }

    /**
     * @param  array<int, mixed>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(array $history, string $question, string $country): array
    {
        $conversation = [];
        $hasUserMessage = false;

        foreach (array_slice($history, -8) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $role = ($entry['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $content = mb_substr(trim((string) ($entry['content'] ?? '')), 0, 2000);

            // The welcome message is UI copy, not part of the actual conversation.
            if ($content === '' || ($role === 'assistant' && ! $hasUserMessage)) {
                continue;
            }

            $conversation[] = ['role' => $role, 'content' => $content];
            $hasUserMessage = $hasUserMessage || $role === 'user';
        }

        // Livewire stores the new user message before making the API call. Avoid
        // sending that same question twice, which can make the model over-focus on it.
        $last = end($conversation);
        if (
            is_array($last)
            && $last['role'] === 'user'
            && $this->normalizeText($last['content']) === $this->normalizeText($question)
        ) {
            array_pop($conversation);
        }

        return [
            ['role' => 'system', 'content' => $this->systemPrompt($country)],
            ...$conversation,
            ['role' => 'user', 'content' => $question],
        ];
    }

    /**
     * @return array{answer: string, links: array<int, array{title: string, url: string}>}
     */
    private function parseResponse(string $rawText, string $country): array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $rawText) ?? $rawText;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;
        $parsed = json_decode(trim($cleaned), true);

        if (! is_array($parsed) || ! is_string($parsed['answer'] ?? null) || blank($parsed['answer'])) {
            Log::warning('Assistant Groq returned an invalid response format.');

            return ['answer' => __('aho.assistant.error_retry'), 'links' => []];
        }

        $allowedLinks = [];
        foreach ($this->sections($country) as $section) {
            $allowedLinks[rtrim($section['url'], '/')] = [
                'title' => $section['title'],
                'url' => $section['url'],
            ];
        }

        $links = [];
        foreach ((array) ($parsed['links'] ?? []) as $link) {
            if (! is_array($link)) {
                continue;
            }

            $url = rtrim(trim((string) ($link['url'] ?? '')), '/');
            if ($url !== '' && isset($allowedLinks[$url])) {
                $links[$url] = $allowedLinks[$url];
            }
        }

        return [
            'answer' => trim($parsed['answer']),
            'links' => array_slice(array_values($links), 0, 3),
        ];
    }

    private function systemPrompt(string $country): string
    {
        $sections = collect($this->sections($country))
            ->map(fn (array $section): string => "- {$section['title']}: {$section['url']} — {$section['description']}")
            ->implode("\n");

        return <<<PROMPT
You are the AHO Assistant inside the WHO Africa Data Capture Tool. You are both a product guide and a concise explainer for concepts related to health observatories, the African Health Observatory (AHO), health data, and this application.

ANSWERING PRIORITIES:
1. Identify the user's exact intent and answer it directly before suggesting any menu.
2. Respond in the same language as the latest user question: French, English, or Portuguese.
3. For a definition or conceptual question (for example "à quoi sert...", "qu'est-ce que...", "why..."), explain the concept. Do not turn it into navigation instructions and do not add links unless a section genuinely helps.
4. For navigation or a how-to question, give precise application-specific guidance. Use numbered steps, with at most 7 steps.
5. Use conversation history to resolve follow-up questions. If a request remains ambiguous, state the likely interpretation and ask one short clarifying question.
6. Do not invent features, permissions, records, statistics, or actions. Menu availability can depend on the user's permissions.
7. Keep simple answers concise, but include enough detail to answer the request accurately.

IMPORTANT DISTINCTIONS ABOUT OBSERVATORIES:
- In general, a health observatory organizes the collection, integration, analysis, interpretation, and sharing of health information. It supports monitoring trends and inequalities, evidence-informed decisions, planning, evaluation, and collaboration. It is not merely a dashboard or a data-entry form.
- AHO means African Health Observatory. This Data Capture Tool supports the data collection and management layer of the AHO ecosystem.
- This application DOES have a "National Observatories" section. It configures a country's observatory identity and presentation, including ownership, titles, contact details, branding, address, footer, and announcements. There can be only one national observatory per country.
- If asked what an observatory is for, explain its purpose. If asked how to create, add, configure, or modify one, direct the user to National Observatories. Never claim that the menu does not exist.

AVAILABLE APPLICATION SECTIONS:
{$sections}

OUTPUT REQUIREMENTS:
- Return valid JSON only, with exactly this shape:
{"answer":"direct response in the user's language","links":[{"title":"Section name","url":"exact URL from the list above"}]}
- Include zero to three links. Use only exact URLs from AVAILABLE APPLICATION SECTIONS.
- For conceptual questions, links should usually be an empty array.
PROMPT;
    }

    /**
     * @return array<int, array{title: string, url: string, description: string}>
     */
    private function sections(string $country): array
    {
        $base = url('/admin/'.$country);

        return [
            ['title' => __('Dashboard'), 'url' => $base, 'description' => 'overview charts and statistics'],
            ['title' => __('aho.resources.indicator_values.navigation'), 'url' => $base.'/indicators/values', 'description' => 'view, enter, review, and validate health indicator data'],
            ['title' => __('aho.actions.add_indicator_value'), 'url' => $base.'/indicators/values/create', 'description' => 'create one indicator value'],
            ['title' => __('aho.resources.indicators.navigation'), 'url' => $base.'/indicators/definitions', 'description' => 'indicator metadata, AFROcode, and descriptions'],
            ['title' => __('aho.resources.indicator_archives.navigation'), 'url' => $base.'/indicators/archives', 'description' => 'historical indicator data'],
            ['title' => __('aho.resources.imports.navigation'), 'url' => $base.'/indicators/imports', 'description' => 'bulk upload indicator data from CSV'],
            ['title' => __('aho.resources.exports.navigation'), 'url' => $base.'/indicators/exports', 'description' => 'prepare and download data exports'],
            ['title' => __('aho.resources.data_integration_connections.navigation'), 'url' => $base.'/data-integration/connections', 'description' => 'configure external data connections and field mappings'],
            ['title' => __('aho.resources.indicator_quality_checks.navigation'), 'url' => $base.'/data-quality/indicator-checks', 'description' => 'inspect quality alerts, missing values, and consistency checks'],
            ['title' => __('aho.resources.locations.navigation'), 'url' => $base.'/regions/locations', 'description' => 'manage countries and geographic levels'],
            ['title' => __('aho.resources.national_observatories.navigation'), 'url' => $base.'/national-observatory/national-observatories', 'description' => 'create or configure the single national observatory associated with a country'],
            ['title' => __('aho.resources.health_facilities.navigation'), 'url' => $base.'/facilities/facilities', 'description' => 'manage health facility records and service information'],
            ['title' => __('aho.resources.health_workforce_values.navigation'), 'url' => $base.'/health-workforce/values', 'description' => 'manage health workforce data values'],
            ['title' => __('aho.resources.health_service_values.navigation'), 'url' => $base.'/health-services/values', 'description' => 'manage health service data values'],
            ['title' => __('aho.resources.data_element_values.navigation'), 'url' => $base.'/data-elements/values', 'description' => 'manage data element values'],
            ['title' => __('aho.resources.knowledge_products.navigation'), 'url' => $base.'/publications/products', 'description' => 'manage publications, reports, and documents'],
            ['title' => __('aho.resources.users.navigation'), 'url' => $base.'/authentication/users', 'description' => 'create and manage user accounts'],
            ['title' => __('aho.resources.roles.navigation'), 'url' => $base.'/authentication/roles', 'description' => 'define roles and access rights'],
            ['title' => __('aho.resources.api_tokens.navigation'), 'url' => $base.'/api-tokens/status', 'description' => 'manage API keys and inspect endpoints'],
            ['title' => __('aho.menus.uhc_clock'), 'url' => $base.'/uhc-clock', 'description' => 'track UHC priority indicators'],
        ];
    }

    private function normalizeCountry(string $country): string
    {
        return preg_match('/^(af|[a-z]{2})$/i', $country) === 1 ? strtolower($country) : 'af';
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value));
    }
}
