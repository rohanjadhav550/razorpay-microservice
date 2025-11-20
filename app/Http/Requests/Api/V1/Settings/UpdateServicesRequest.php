<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServicesRequest extends FormRequest
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
            'services' => ['required', 'array'],
            'services.*' => ['required', 'string', Rule::in(['payment_link', 'order', 'agent'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'services.required' => 'Services selection is required.',
            'services.array' => 'Services must be an array.',
            'services.*.in' => 'Invalid service selected. Valid services are: payment_link, order, agent.',
        ];
    }
}
