<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('shows account settings', function () {
    $user = User::factory()->withRazorpay()->withAnthropic()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/settings');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'razorpay_configured' => true,
                'anthropic_configured' => true,
                'openai_configured' => false,
                'ai_provider' => 'anthropic',
            ],
        ]);
});

it('updates account info', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/settings', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Account settings updated successfully.',
        ]);

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

it('updates razorpay credentials', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/settings/razorpay', [
        'razorpay_key_id' => 'rzp_test_123456789',
        'razorpay_key_secret' => 'secret_key_here',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Razorpay credentials updated successfully.',
        ]);

    $user->refresh();
    expect($user->hasRazorpayConfigured())->toBeTrue();
});

it('updates ai provider settings', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/settings/anthropic', [
        'ai_provider' => 'openai',
        'openai_api_key' => 'sk-test-key-123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'AI provider settings updated successfully.',
            'data' => [
                'ai_provider' => 'openai',
                'openai_configured' => true,
            ],
        ]);

    $user->refresh();
    expect($user->ai_provider)->toBe('openai');
    expect($user->hasOpenAIConfigured())->toBeTrue();
});

it('fails with invalid ai provider', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/settings/anthropic', [
        'ai_provider' => 'invalid_provider',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ai_provider']);
});

it('requires authentication for settings', function () {
    $response = $this->getJson('/api/v1/settings');
    $response->assertUnauthorized();
});
