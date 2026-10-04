<?php

namespace App\Models;

use App\Enums\UserNotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'dedupe_key',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'type' =>
            UserNotificationType::class,

        'data' => 'array',

        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
