<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tier',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isProTier(): bool
    {
        return $this->tier === 'pro';
    }

    public function isBuilderTier(): bool
    {
        return $this->tier === 'builder';
    }

    public function isFreeTier(): bool
    {
        return $this->tier === 'free';
    }

    /**
     * Number of days of history available for this tier.
     * null = unlimited.
     */
    public function historyLimitDays(): ?int
    {
        return match ($this->tier) {
            'pro'     => null,
            'builder' => 90,
            default   => 7,
        };
    }

    /**
     * Daily API rate limit for this tier.
     */
    public function dailyRateLimit(): int
    {
        return match ($this->tier) {
            'pro'     => 100000,
            'builder' => 10000,
            default   => 100,
        };
    }
}
