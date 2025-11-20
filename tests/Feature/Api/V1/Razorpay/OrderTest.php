<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test123',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => 'created',
        ], 200),
        'api.razorpay.com/v1/orders/*' => Http::response([
            'id' => 'order_test123',
            'amount' => 100000,
            'status' => 'created',
        ], 200),
        'api.razorpay.com/v1/orders/*/payments' => Http::response([
            'items' => [],
            'count' => 0,
        ], 200),
    ]);
});

it('creates an order', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['order'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/orders', [
        'amount' => 100000,
        'receipt' => 'receipt_123',
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Order created successfully.',
        ]);
});

it('gets an order', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['order'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/orders/order_test123');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('gets order payments', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['order'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/orders/order_test123/payments');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('fails without service enabled', function () {
    $user = User::factory()
        ->withRazorpay()
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/orders', [
        'amount' => 100000,
    ]);

    $response->assertForbidden();
});

it('validates required fields', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['order'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/orders', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});
