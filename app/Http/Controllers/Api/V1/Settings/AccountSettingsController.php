<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdateAIProviderRequest;
use App\Http\Requests\Api\V1\Settings\UpdateRazorpayRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'razorpay_configured' => $user->hasRazorpayConfigured(),
                'anthropic_configured' => $user->hasAnthropicConfigured(),
                'openai_configured' => $user->hasOpenAIConfigured(),
                'ai_provider' => $user->ai_provider,
                'enabled_services' => $user->enabled_services ?? [],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Account settings updated successfully.',
            'data' => $request->user()->only(['id', 'name', 'email', 'updated_at']),
        ]);
    }

    public function updateRazorpay(UpdateRazorpayRequest $request): JsonResponse
    {
        $request->user()->update([
            'razorpay_key_id' => $request->razorpay_key_id,
            'razorpay_key_secret' => $request->razorpay_key_secret,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Razorpay credentials updated successfully.',
        ]);
    }

    public function updateAnthropic(UpdateAIProviderRequest $request): JsonResponse
    {
        $updateData = [];

        if ($request->has('ai_provider')) {
            $updateData['ai_provider'] = $request->ai_provider;
        }

        if ($request->has('anthropic_api_key')) {
            $updateData['anthropic_api_key'] = $request->anthropic_api_key;
        }

        if ($request->has('openai_api_key')) {
            $updateData['openai_api_key'] = $request->openai_api_key;
        }

        if (! empty($updateData)) {
            $request->user()->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'AI provider settings updated successfully.',
            'data' => [
                'ai_provider' => $request->user()->fresh()->ai_provider,
                'anthropic_configured' => $request->user()->hasAnthropicConfigured(),
                'openai_configured' => $request->user()->hasOpenAIConfigured(),
            ],
        ]);
    }
}
