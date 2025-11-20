<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
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
                'data' => request()->user()->only(['id', 'name', 'email', 'enabled_services', 'created_at']),
            ]);
        })->name('api.user');
    });
});
