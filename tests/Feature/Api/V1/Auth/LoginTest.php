<?php

use App\Models\User;

it('logs in successfully with valid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'enabled_services', 'ai_provider', 'created_at'],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Login successful.',
        ]);
});

it('fails to login with invalid email', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'wrong@example.com',
        'password' => 'Password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('fails to login with invalid password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'WrongPassword123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('accepts custom device name', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123',
        'device_name' => 'iPhone 15',
    ]);

    $response->assertOk();
});
