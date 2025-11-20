<?php

namespace App\Http\Requests\Api\V1\Razorpay;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentLinkRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'accept_partial' => ['sometimes', 'boolean'],
            'first_min_partial_amount' => ['sometimes', 'integer', 'min:100'],
            'description' => ['required', 'string', 'max:2048'],
            'customer' => ['sometimes', 'array'],
            'customer.name' => ['sometimes', 'string', 'max:255'],
            'customer.email' => ['sometimes', 'email', 'max:255'],
            'customer.contact' => ['sometimes', 'string', 'max:20'],
            'notify' => ['sometimes', 'array'],
            'notify.sms' => ['sometimes', 'boolean'],
            'notify.email' => ['sometimes', 'boolean'],
            'reminder_enable' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'array'],
            'callback_url' => ['sometimes', 'url', 'max:2048'],
            'callback_method' => ['sometimes', 'string', 'in:get,post'],
            'expire_by' => ['sometimes', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be at least 100 (1 INR in paise).',
            'description.required' => 'Description is required.',
        ];
    }
}
