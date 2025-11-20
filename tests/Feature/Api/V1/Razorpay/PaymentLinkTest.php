<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links' => Http::response([
            'id' => 'plink_test123',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/test',
        ], 200),
        'api.razorpay.com/v1/payment_links/*' => Http::response([
            'id' => 'plink_test123',
            'amount' => 100000,
            'status' => 'created',
        ], 200),
    ]);
});

it('creates a payment link', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['payment_link'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/payment-links', [
        'amount' => 100000,
        'description' => 'Test payment',
        'customer' => [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ],
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Payment link created successfully.',
        ]);
});

it('gets a payment link', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['payment_link'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/payment-links/plink_test123');

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

    $response = $this->postJson('/api/v1/payment-links', [
        'amount' => 100000,
        'description' => 'Test payment',
    ]);

    $response->assertForbidden();
});

it('fails without razorpay configured', function () {
    $user = User::factory()
        ->withServices(['payment_link'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/payment-links', [
        'amount' => 100000,
        'description' => 'Test payment',
    ]);

    $response->assertForbidden();
});

it('validates required fields', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['payment_link'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/payment-links', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'description']);
});

it('validates minimum amount', function () {
    $user = User::factory()
        ->withRazorpay()
        ->withServices(['payment_link'])
        ->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/payment-links', [
        'amount' => 50,
        'description' => 'Test',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});
