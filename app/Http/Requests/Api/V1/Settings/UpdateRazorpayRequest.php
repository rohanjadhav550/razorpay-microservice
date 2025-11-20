<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRazorpayRequest extends FormRequest
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
            'razorpay_key_id' => ['required', 'string', 'max:255'],
            'razorpay_key_secret' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'razorpay_key_id.required' => 'Razorpay Key ID is required.',
            'razorpay_key_secret.required' => 'Razorpay Key Secret is required.',
        ];
    }
}
