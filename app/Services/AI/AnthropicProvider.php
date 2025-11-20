<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AIProviderInterface
{
    private string $baseUrl = 'https://api.anthropic.com/v1';

    private string $model = 'claude-sonnet-4-20250514';

    public function __construct(
        private string $apiKey
    ) {}

    public function chat(array $messages, ?string $systemPrompt = null): string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => $this->formatMessages($messages),
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::timeout(120)->withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post("{$this->baseUrl}/messages", $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Anthropic API call failed: '.$response->body());
        }

        $data = $response->json();

        return $data['content'][0]['text'] ?? '';
    }

    public function streamChat(array $messages, ?string $systemPrompt = null): \Generator
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => $this->formatMessages($messages),
            'stream' => true,
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/messages", $payload);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = $body->read(1024);
            if (str_contains($line, 'content_block_delta')) {
                preg_match('/"text":"([^"]*)"/', $line, $matches);
                if (! empty($matches[1])) {
                    yield $matches[1];
                }
            }
        }
    }

    private function formatMessages(array $messages): array
    {
        return array_map(function ($message) {
            return [
                'role' => $message['role'] === 'user' ? 'user' : 'assistant',
                'content' => $message['content'],
            ];
        }, $messages);
    }
}
