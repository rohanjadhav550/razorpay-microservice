<?php

namespace App\Http\Requests\Api\V1\Razorpay;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'receipt' => ['sometimes', 'string', 'max:40'],
            'notes' => ['sometimes', 'array'],
            'partial_payment' => ['sometimes', 'boolean'],
            'first_payment_min_amount' => ['sometimes', 'integer', 'min:100'],
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
            'receipt.max' => 'Receipt must not exceed 40 characters.',
        ];
    }
}
