<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdateServicesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceSelectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $availableServices = [
            [
                'id' => 'payment_link',
                'name' => 'Payment Link',
                'description' => 'Generate and manage Razorpay payment links',
                'requires' => ['razorpay'],
            ],
            [
                'id' => 'order',
                'name' => 'Order Management',
                'description' => 'Create and manage Razorpay orders',
                'requires' => ['razorpay'],
            ],
            [
                'id' => 'agent',
                'name' => 'AI Agent',
                'description' => 'AI-powered workflow automation with database integration',
                'requires' => ['ai_provider'],
            ],
        ];

        $enabledServices = $request->user()->enabled_services ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'available_services' => $availableServices,
                'enabled_services' => $enabledServices,
            ],
        ]);
    }

    public function update(UpdateServicesRequest $request): JsonResponse
    {
        $request->user()->update([
            'enabled_services' => $request->services,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Services updated successfully.',
            'data' => [
                'enabled_services' => $request->services,
            ],
        ]);
    }
}
