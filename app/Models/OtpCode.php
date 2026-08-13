<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
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

    /**
     * هل انتهت صلاحية الكود؟
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * هل استهلك المستخدم جميع محاولات إدخال الكود؟
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    /**
     * هل تم استخدام الكود والتحقق منه مسبقًا؟
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * هل رقم الهاتف محظور مؤقتًا من طلب OTP؟
     */
    public function isBlocked(): bool
    {
        return $this->blocked_until !== null
            && $this->blocked_until->isFuture();
    }
}