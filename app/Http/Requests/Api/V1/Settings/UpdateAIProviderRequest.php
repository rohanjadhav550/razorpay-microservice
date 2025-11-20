<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAIProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ai_provider' => ['sometimes', 'string', Rule::in(['anthropic', 'openai'])],
            'anthropic_api_key' => ['nullable', 'string', 'max:255'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ai_provider.in' => 'AI provider must be either anthropic or openai.',
        ];
    }
}
