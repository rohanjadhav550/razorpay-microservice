<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceModeFilter
{
    /**
     * Routes allowed per service mode.
     *
     * @var array<string, array<string>>
     */
    private array $allowedRoutes = [
        'payment_link' => [
            'api.register',
            'api.login',
            'api.logout',
            'api.password.update',
            'api.settings.show',
            'api.settings.update',
            'api.settings.razorpay',
            'api.user',
            'api.payment-links.index',
            'api.payment-links.store',
            'api.payment-links.show',
            'api.payment-links.cancel',
        ],
        'order' => [
            'api.register',
            'api.login',
            'api.logout',
            'api.password.update',
            'api.settings.show',
            'api.settings.update',
            'api.settings.razorpay',
            'api.user',
            'api.orders.index',
            'api.orders.store',
            'api.orders.show',
            'api.orders.payments',
        ],
        'agent' => [
            'api.register',
            'api.login',
            'api.logout',
            'api.password.update',
            'api.settings.show',
            'api.settings.update',
            'api.settings.anthropic',
            'api.user',
            'api.database-connections.index',
            'api.database-connections.store',
            'api.database-connections.show',
            'api.database-connections.update',
            'api.database-connections.destroy',
            'api.database-connections.test',
            'api.database-connections.schema',
            'api.agent.conversations',
            'api.agent.conversations.start',
            'api.agent.conversations.show',
            'api.agent.conversations.chat',
            'api.agent.proposals',
            'api.agent.proposals.generate',
            'api.agent.proposals.show',
            'api.agent.proposals.approve',
            'api.agent.proposals.apply',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $serviceMode = env('SERVICE_MODE');

        // If no service mode is set, allow all routes (full stack mode)
        if (empty($serviceMode)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // Allow health check
        if ($request->is('up')) {
            return $next($request);
        }

        // Check if route is allowed for this service mode
        if ($routeName && isset($this->allowedRoutes[$serviceMode])) {
            if (! in_array($routeName, $this->allowedRoutes[$serviceMode], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "This endpoint is not available in {$serviceMode} service mode.",
                ], 404);
            }
        }

        return $next($request);
    }
}
