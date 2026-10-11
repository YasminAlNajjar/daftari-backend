<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    private const BALANCE_EXPRESSION = <<<'SQL'
        COALESCE(
            SUM(
                CASE
                    WHEN transactions.type = ? THEN transactions.amount
                    WHEN transactions.type = ? THEN -transactions.amount
                    ELSE 0
                END
            ),
            0
        )
        SQL;

    protected $fillable = [
        'name',
        'phone',
        'notes',
        'credit_limit',
        'client_uuid'
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
        ];
    }

    /**
     * التاجر صاحب الزبون.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * معاملات الزبون: ديون ودفعات.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query
            ->select('customers.*')
            ->selectSub(
                Transaction::query()
                    ->selectRaw(
                        self::BALANCE_EXPRESSION,
                        [
                            Transaction::TYPE_PAYMENT,
                            Transaction::TYPE_DEBT,
                        ]
                    )
                    ->whereColumn(
                        'transactions.customer_id',
                        'customers.id'
                    ),
                'balance'
            );
    }
     
     /**
     * حساب رصيد الزبون
     */

    public function getBalanceAttribute(mixed $value = null): string
    {
        if ($value === null) {
            $result = $this->transactions()
                ->selectRaw(
                    self::BALANCE_EXPRESSION.' AS calculated_balance',
                    [
                        Transaction::TYPE_PAYMENT,
                        Transaction::TYPE_DEBT,
                    ]
                )
                ->first();

            $value = $result?->getRawOriginal('calculated_balance') ?? 0;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
