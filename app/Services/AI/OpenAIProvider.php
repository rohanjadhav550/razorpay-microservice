<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProviderInterface
{
    private string $baseUrl = 'https://api.openai.com/v1';

    private string $model = 'gpt-4o';

    public function __construct(
        private string $apiKey
    ) {}

    public function chat(array $messages, ?string $systemPrompt = null): string
    {
        $formattedMessages = [];

        if ($systemPrompt) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => $formattedMessages,
            'max_tokens' => 4096,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API call failed: '.$response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function streamChat(array $messages, ?string $systemPrompt = null): \Generator
    {
        $formattedMessages = [];

        if ($systemPrompt) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => $formattedMessages,
                'max_tokens' => 4096,
                'stream' => true,
            ]);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = $body->read(1024);
            if (str_contains($line, '"delta"')) {
                preg_match('/"content":"([^"]*)"/', $line, $matches);
                if (! empty($matches[1])) {
                    yield $matches[1];
                }
            }
        }
    }
}
