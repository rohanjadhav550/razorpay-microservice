<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('logs out successfully', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
});

it('fails to logout without authentication', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertUnauthorized();
});
