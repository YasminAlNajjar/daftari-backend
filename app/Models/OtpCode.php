<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code_hash',
        'attempts',
        'send_count',
        'send_window_started_at',
        'blocked_until',
        'expires_at',
        'last_sent_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'send_count' => 'integer',
            'send_window_started_at' => 'datetime',
            'blocked_until' => 'datetime',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_until !== null
            && $this->blocked_until->isFuture();
    }
}