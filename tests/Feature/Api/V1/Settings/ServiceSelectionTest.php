<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists available services', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/services');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'available_services' => [
                    '*' => ['id', 'name', 'description', 'requires'],
                ],
                'enabled_services',
            ],
        ]);
});

it('updates enabled services', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/services', [
        'services' => ['payment_link', 'order'],
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Services updated successfully.',
            'data' => [
                'enabled_services' => ['payment_link', 'order'],
            ],
        ]);

    $user->refresh();
    expect($user->enabled_services)->toBe(['payment_link', 'order']);
});

it('fails with invalid service', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/services', [
        'services' => ['invalid_service'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['services.0']);
});

it('accepts agent service', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/services', [
        'services' => ['agent'],
    ]);

    $response->assertOk();
});

it('requires authentication for services', function () {
    $response = $this->getJson('/api/v1/services');
    $response->assertUnauthorized();
});
