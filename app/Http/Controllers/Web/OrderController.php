<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RazorpayClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Orders/Index');
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'receipt' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['string', 'max:255'],
            'partial_payment' => ['nullable', 'boolean'],
            'first_payment_min_amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        $user = $request->user();

        if (! $user->hasRazorpayConfigured()) {
            return back()->withErrors(['razorpay' => 'Please configure your Razorpay credentials first.']);
        }

        try {
            $client = new RazorpayClient(
                $user->razorpay_key_id,
                $user->razorpay_key_secret
            );

            $order = $client->createOrder($validated);

            return back()->with([
                'success' => 'Order created successfully.',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['razorpay' => 'Failed to create order: ' . $e->getMessage()]);
        }
    }
}
