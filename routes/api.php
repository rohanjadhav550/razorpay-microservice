<?php

use App\Http\Controllers\Api\V1\Agent\AgentController;
use App\Http\Controllers\Api\V1\Agent\DatabaseConnectionController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Razorpay\OrderController;
use App\Http\Controllers\Api\V1\Razorpay\PaymentLinkController;
use App\Http\Controllers\Api\V1\Settings\AccountSettingsController;
use App\Http\Controllers\Api\V1\Settings\ServiceSelectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes return JSON responses. API versioning is implemented via
| route prefixes (v1, v2, etc.)
|
*/

Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('/register', RegisterController::class)->name('api.register');
    Route::post('/login', LoginController::class)->name('api.login');

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', LogoutController::class)->name('api.logout');
        Route::put('/password', PasswordController::class)->name('api.password.update');

        // Account Settings
        Route::get('/settings', [AccountSettingsController::class, 'show'])->name('api.settings.show');
        Route::put('/settings', [AccountSettingsController::class, 'update'])->name('api.settings.update');
        Route::put('/settings/razorpay', [AccountSettingsController::class, 'updateRazorpay'])->name('api.settings.razorpay');
        Route::put('/settings/anthropic', [AccountSettingsController::class, 'updateAnthropic'])->name('api.settings.anthropic');

        // Service Selection
        Route::get('/services', [ServiceSelectionController::class, 'index'])->name('api.services.index');
        Route::put('/services', [ServiceSelectionController::class, 'update'])->name('api.services.update');

        // User info
        Route::get('/user', function () {
            return response()->json([
                'success' => true,
                'data' => request()->user()->only(['id', 'name', 'email', 'enabled_services', 'ai_provider', 'created_at']),
            ]);
        })->name('api.user');

        // Payment Link Service
        Route::middleware('service:payment_link')->group(function () {
            Route::get('/payment-links', [PaymentLinkController::class, 'index'])->name('api.payment-links.index');
            Route::post('/payment-links', [PaymentLinkController::class, 'store'])->name('api.payment-links.store');
            Route::get('/payment-links/{id}', [PaymentLinkController::class, 'show'])->name('api.payment-links.show');
            Route::post('/payment-links/{id}/cancel', [PaymentLinkController::class, 'cancel'])->name('api.payment-links.cancel');
        });

        // Order Service
        Route::middleware('service:order')->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
            Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
            Route::get('/orders/{id}', [OrderController::class, 'show'])->name('api.orders.show');
            Route::get('/orders/{id}/payments', [OrderController::class, 'payments'])->name('api.orders.payments');
        });

        // Agent Service
        Route::middleware('service:agent')->group(function () {
            // Database Connections
            Route::get('/database-connections', [DatabaseConnectionController::class, 'index'])->name('api.database-connections.index');
            Route::post('/database-connections', [DatabaseConnectionController::class, 'store'])->name('api.database-connections.store');
            Route::get('/database-connections/{connection}', [DatabaseConnectionController::class, 'show'])->name('api.database-connections.show');
            Route::put('/database-connections/{connection}', [DatabaseConnectionController::class, 'update'])->name('api.database-connections.update');
            Route::delete('/database-connections/{connection}', [DatabaseConnectionController::class, 'destroy'])->name('api.database-connections.destroy');
            Route::post('/database-connections/{connection}/test', [DatabaseConnectionController::class, 'test'])->name('api.database-connections.test');
            Route::get('/database-connections/{connection}/schema', [DatabaseConnectionController::class, 'schema'])->name('api.database-connections.schema');

            // Agent Conversations
            Route::get('/agent/conversations', [AgentController::class, 'conversations'])->name('api.agent.conversations');
            Route::post('/agent/conversations', [AgentController::class, 'startConversation'])->name('api.agent.conversations.start');
            Route::get('/agent/conversations/{conversation}', [AgentController::class, 'getConversation'])->name('api.agent.conversations.show');
            Route::post('/agent/conversations/{conversation}/chat', [AgentController::class, 'chat'])->name('api.agent.conversations.chat');

            // Schema Proposals
            Route::get('/agent/proposals', [AgentController::class, 'proposals'])->name('api.agent.proposals');
            Route::post('/agent/proposals', [AgentController::class, 'generateProposal'])->name('api.agent.proposals.generate');
            Route::get('/agent/proposals/{proposal}', [AgentController::class, 'showProposal'])->name('api.agent.proposals.show');
            Route::post('/agent/proposals/{proposal}/approve', [AgentController::class, 'approveProposal'])->name('api.agent.proposals.approve');
            Route::post('/agent/proposals/{proposal}/apply', [AgentController::class, 'applyProposal'])->name('api.agent.proposals.apply');
        });
    });
});
