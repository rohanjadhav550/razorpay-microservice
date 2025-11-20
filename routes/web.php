<?php

use App\Http\Controllers\Web\AgentController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PaymentLinkController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

// Temporary route to clear PHP opcache - remove after use
Route::get('/clear-cache', function () {
    if (function_exists('opcache_reset')) {
        opcache_reset();
        return 'OPcache cleared!';
    }
    return 'OPcache not available';
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect('/login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/services', [SettingsController::class, 'updateServices'])->name('settings.services');
    Route::put('/settings/razorpay', [SettingsController::class, 'updateRazorpay'])->name('settings.razorpay');
    Route::put('/settings/ai', [SettingsController::class, 'updateAI'])->name('settings.ai');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Payment Links
    Route::get('/payment-links', [PaymentLinkController::class, 'index'])->name('payment-links');
    Route::post('/payment-links', [PaymentLinkController::class, 'create'])->name('payment-links.create');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::post('/orders', [OrderController::class, 'create'])->name('orders.create');

    // Agent
    Route::get('/agent', [AgentController::class, 'index'])->name('agent');
    Route::post('/agent/conversations', [AgentController::class, 'createConversation'])->name('agent.conversations.create');
    Route::get('/agent/conversations/{conversation}', [AgentController::class, 'conversation'])->name('agent.conversation');
    Route::post('/agent/conversations/{conversation}/chat', [AgentController::class, 'chat'])->name('agent.chat');
    Route::post('/agent/conversations/{conversation}/generate-proposal', [AgentController::class, 'generateProposal'])->name('agent.generate-proposal');

    // Proposals
    Route::post('/agent/proposals/{proposal}/approve', [AgentController::class, 'approveProposal'])->name('agent.proposals.approve');
    Route::post('/agent/proposals/{proposal}/reject', [AgentController::class, 'rejectProposal'])->name('agent.proposals.reject');
    Route::post('/agent/proposals/{proposal}/apply', [AgentController::class, 'applyMigrations'])->name('agent.proposals.apply');

    // Database Connections
    Route::post('/agent/connections', [AgentController::class, 'storeConnection'])->name('agent.connections.store');
    Route::post('/agent/connections/{connection}/test', [AgentController::class, 'testConnection'])->name('agent.connections.test');
});
