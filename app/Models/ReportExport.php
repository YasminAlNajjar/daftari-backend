<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportExport extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Report Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_DAILY_INVENTORY =
        'daily_inventory';

    public const TYPE_CUSTOMER_STATEMENT =
        'customer_statement';

    public const TYPE_GENERAL_TRANSACTIONS =
        'general_transactions';

    public const TYPE_CUSTOMER_BALANCES =
        'customer_balances';


    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING =
        'pending';

    public const STATUS_PROCESSING =
        'processing';

    public const STATUS_COMPLETED =
        'completed';

    public const STATUS_FAILED =
        'failed';


    protected $fillable = [
        'user_id',
        'report_type',
        'parameters',
        'status',
        'temporary_file_path',
        'error_message',
        'completed_at',
        'expires_at',
    ];


    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
 
    public function certificate(): HasOne
    {
    return $this->hasOne(ReportCertificate::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function reportTypes(): array
    {
        return [
            self::TYPE_DAILY_INVENTORY,
            self::TYPE_CUSTOMER_STATEMENT,
            self::TYPE_GENERAL_TRANSACTIONS,
            self::TYPE_CUSTOMER_BALANCES,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}