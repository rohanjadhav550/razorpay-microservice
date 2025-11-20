<?php

namespace App\Services\AI;

use App\Models\User;

class AIService
{
    private AIProviderInterface $provider;

    public function __construct(User $user)
    {
        if (! $user->hasAIConfigured()) {
            throw new \RuntimeException('AI provider is not configured.');
        }

        $this->provider = match ($user->ai_provider) {
            'anthropic' => new AnthropicProvider($user->anthropic_api_key),
            'openai' => new OpenAIProvider($user->openai_api_key),
            default => throw new \RuntimeException("Unsupported AI provider: {$user->ai_provider}"),
        };
    }

    public function chat(array $messages, ?string $systemPrompt = null): string
    {
        return $this->provider->chat($messages, $systemPrompt);
    }

    public function streamChat(array $messages, ?string $systemPrompt = null): \Generator
    {
        return $this->provider->streamChat($messages, $systemPrompt);
    }

    public function analyzeRequirements(string $userPrompt, ?array $existingSchema = null): string
    {
        $systemPrompt = $this->getAnalysisSystemPrompt();

        $messages = [
            [
                'role' => 'user',
                'content' => $this->buildAnalysisPrompt($userPrompt, $existingSchema),
            ],
        ];

        return $this->chat($messages, $systemPrompt);
    }

    public function generateERDiagram(array $proposedSchema): string
    {
        $systemPrompt = 'You are a database schema expert. Generate Mermaid ER diagram syntax for the given schema. Only output the Mermaid code, no explanations.';

        $messages = [
            [
                'role' => 'user',
                'content' => "Generate a Mermaid ER diagram for this schema:\n\n".json_encode($proposedSchema, JSON_PRETTY_PRINT),
            ],
        ];

        return $this->chat($messages, $systemPrompt);
    }

    public function generateMigrations(array $proposedChanges, string $databaseDriver): string
    {
        $systemPrompt = "You are a database migration expert. Generate SQL migration statements for {$databaseDriver}. Output only the SQL code with comments explaining each change.";

        $messages = [
            [
                'role' => 'user',
                'content' => "Generate migrations for these changes:\n\n".json_encode($proposedChanges, JSON_PRETTY_PRINT),
            ],
        ];

        return $this->chat($messages, $systemPrompt);
    }

    private function getAnalysisSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a database architect and API integration expert. Your role is to:

1. Analyze user requirements for integrating Razorpay payment services
2. Design database schemas that support their use case
3. Propose tables, columns, relationships, and indexes
4. Consider data types, constraints, and foreign keys

Always respond with a JSON structure containing:
- "understanding": Summary of what the user wants
- "proposed_tables": Array of table definitions
- "relationships": Array of relationships between tables
- "workflow_logic": Description of how the workflow should operate
- "razorpay_integration": How Razorpay data maps to their schema

Be thorough but practical. Consider edge cases and data integrity.
PROMPT;
    }

    private function buildAnalysisPrompt(string $userPrompt, ?array $existingSchema): string
    {
        $prompt = "User requirement: {$userPrompt}\n\n";

        if ($existingSchema) {
            $prompt .= "Existing database schema:\n".json_encode($existingSchema, JSON_PRETTY_PRINT)."\n\n";
            $prompt .= "Analyze how to extend this existing schema to support the user's requirements.\n";
        } else {
            $prompt .= "No existing schema provided. Design a complete schema from scratch.\n";
        }

        return $prompt;
    }
}
