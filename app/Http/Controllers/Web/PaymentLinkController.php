<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RazorpayClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentLinkController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PaymentLinks/Index');
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['required', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:20'],
            'expire_by' => ['nullable', 'integer', 'min:' . time()],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'callback_method' => ['nullable', 'string', 'in:get,post'],
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

            $paymentLink = $client->createPaymentLink($validated);

            return back()->with([
                'success' => 'Payment link created successfully.',
                'payment_link' => $paymentLink,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['razorpay' => 'Failed to create payment link: ' . $e->getMessage()]);
        }
    }
}
