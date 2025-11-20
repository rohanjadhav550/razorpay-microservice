<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('updates password successfully', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123',
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/password', [
        'current_password' => 'OldPassword123',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);

    $user->refresh();
    expect(Hash::check('NewPassword123', $user->password))->toBeTrue();
});

it('fails with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123',
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/password', [
        'current_password' => 'WrongPassword123',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('fails with weak new password', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123',
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/password', [
        'current_password' => 'OldPassword123',
        'password' => '123456',
        'password_confirmation' => '123456',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('fails without authentication', function () {
    $response = $this->putJson('/api/v1/password', [
        'current_password' => 'OldPassword123',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    $response->assertUnauthorized();
});
