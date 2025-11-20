<?php

namespace App\Http\Controllers\Api\V1\Razorpay;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Razorpay\CreateOrderRequest;
use App\Services\Razorpay\RazorpayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $params = $request->only(['count', 'skip', 'authorized', 'receipt', 'from', 'to']);

        $response = $client->listOrders($params);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $data = $request->validated();

        // Set default currency if not provided
        if (! isset($data['currency'])) {
            $data['currency'] = 'INR';
        }

        $response = $client->createOrder($data);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $response->json(),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $response = $client->getOrder($id);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function payments(Request $request, string $id): JsonResponse
    {
        $client = new RazorpayClient($request->user());

        $response = $client->getOrderPayments($id);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order payments.',
                'error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }
}
