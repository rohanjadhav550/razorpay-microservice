<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function chat(array $messages, ?string $systemPrompt = null): string;

    public function streamChat(array $messages, ?string $systemPrompt = null): \Generator;
}
