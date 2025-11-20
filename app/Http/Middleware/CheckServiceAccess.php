<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckServiceAccess
{
    public function handle(Request $request, Closure $next, string $service): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->hasService($service)) {
            return response()->json([
                'success' => false,
                'message' => "You don't have access to the {$service} service. Please enable it in your settings.",
            ], 403);
        }

        // Check if Razorpay is configured for payment/order services
        if (in_array($service, ['payment_link', 'order']) && ! $user->hasRazorpayConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Razorpay credentials are not configured. Please add them in your settings.',
            ], 403);
        }

        // Check if AI is configured for agent service
        if ($service === 'agent' && ! $user->hasAIConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'AI provider API key is not configured. Please add it in your settings.',
            ], 403);
        }

        return $next($request);
    }
}
