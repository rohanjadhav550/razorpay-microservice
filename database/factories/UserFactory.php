<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'ai_provider' => 'anthropic',
            'enabled_services' => [],
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRazorpay(): static
    {
        return $this->state(fn (array $attributes) => [
            'razorpay_key_id' => 'rzp_test_'.Str::random(14),
            'razorpay_key_secret' => Str::random(24),
        ]);
    }

    public function withAnthropic(): static
    {
        return $this->state(fn (array $attributes) => [
            'anthropic_api_key' => 'sk-ant-'.Str::random(32),
            'ai_provider' => 'anthropic',
        ]);
    }

    public function withOpenAI(): static
    {
        return $this->state(fn (array $attributes) => [
            'openai_api_key' => 'sk-'.Str::random(48),
            'ai_provider' => 'openai',
        ]);
    }

    public function withServices(array $services): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled_services' => $services,
        ]);
    }
}
