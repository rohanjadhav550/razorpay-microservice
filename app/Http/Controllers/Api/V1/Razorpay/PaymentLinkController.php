<?php

namespace App\Http\Controllers\Api\V1\Razorpay;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Razorpay\CreatePaymentLinkRequest;
use App\Services\Razorpay\RazorpayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $params = $request->only(['count', 'skip', 'payment_id']);

        $response = $client->listPaymentLinks($params);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment links.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function store(CreatePaymentLinkRequest $request): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $data = $request->validated();

        // Set default currency if not provided
        if (! isset($data['currency'])) {
            $data['currency'] = 'INR';
        }

        $response = $client->createPaymentLink($data);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment link.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment link created successfully.',
            'data' => $response->json(),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $response = $client->getPaymentLink($id);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment link.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $response = $client->cancelPaymentLink($id);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment link.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment link cancelled successfully.',
            'data' => $response->json(),
        ]);
    }
}
