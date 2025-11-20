<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'razorpay_key_id',
        'razorpay_key_secret',
        'anthropic_api_key',
        'openai_api_key',
        'ai_provider',
        'enabled_services',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'razorpay_key_id',
        'razorpay_key_secret',
        'anthropic_api_key',
        'openai_api_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'razorpay_key_id' => 'encrypted',
            'razorpay_key_secret' => 'encrypted',
            'anthropic_api_key' => 'encrypted',
            'openai_api_key' => 'encrypted',
            'enabled_services' => 'array',
        ];
    }

    public function hasService(string $service): bool
    {
        return in_array($service, $this->enabled_services ?? [], true);
    }

    public function hasRazorpayConfigured(): bool
    {
        return ! empty($this->razorpay_key_id) && ! empty($this->razorpay_key_secret);
    }

    public function hasAnthropicConfigured(): bool
    {
        return ! empty($this->anthropic_api_key);
    }

    public function hasOpenAIConfigured(): bool
    {
        return ! empty($this->openai_api_key);
    }

    public function hasAIConfigured(): bool
    {
        return match ($this->ai_provider) {
            'anthropic' => $this->hasAnthropicConfigured(),
            'openai' => $this->hasOpenAIConfigured(),
            default => false,
        };
    }

    public function getActiveAIKey(): ?string
    {
        return match ($this->ai_provider) {
            'anthropic' => $this->anthropic_api_key,
            'openai' => $this->openai_api_key,
            default => null,
        };
    }
}
