<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_DEBT = 'debt';
    public const TYPE_PAYMENT = 'payment';

    protected $fillable = [
        'user_id',
        'customer_id',
        'type',
        'amount',
        'description',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * المستخدم الذي سجل المعاملة.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الزبون المرتبط بالمعاملة.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * هل المعاملة دين؟
     */
    public function isDebt(): bool
    {
        return $this->type === self::TYPE_DEBT;
    }

    /**
     * هل المعاملة دفعة؟
     */
    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    public static function types(): array
    {
    return [
        self::TYPE_DEBT,
        self::TYPE_PAYMENT,
    ];
    }

    public function scopeDebts($query)
    {
    return $query->where('type', self::TYPE_DEBT);
    }

    public function scopePayments($query)
    {
    return $query->where('type', self::TYPE_PAYMENT);
    }
}
