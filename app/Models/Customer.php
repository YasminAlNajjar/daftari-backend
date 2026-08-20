<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'notes',
        'credit_limit',
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
     
     /**
     * حساب رصيد الزبون
     */

    public function getBalanceAttribute(): string
    {
        $debts = $this->transactions()
        ->where('type', Transaction::TYPE_DEBT)
        ->sum('amount');

        $payments = $this->transactions()
        ->where('type', Transaction::TYPE_PAYMENT)
        ->sum('amount');

         return number_format($debts - $payments, 2, '.', '');
   }
}
