<?php

namespace App\Services\Notifications;

use App\Enums\UserNotificationType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditLimitNotificationService
{
    private const LEVEL_NORMAL = 0;

    private const LEVEL_NEAR = 1;

    private const LEVEL_REACHED = 2;

    public function __construct(
        private NotificationService $notifications
    ) {}

    public function notifyIfThresholdCrossed(
        User $user,
        Customer $customer,
        float $previousDebt,
        float $resultingDebt,
        ?int $transactionId = null
    ): void {
        $limit = (float) $customer->credit_limit;

        if ($limit <= 0 || $resultingDebt <= $previousDebt) {
            return;
        }

        $previousLevel = $this->level($previousDebt, $limit);
        $resultingLevel = $this->level($resultingDebt, $limit);

        if ($resultingLevel <= $previousLevel) {
            return;
        }

        if ($resultingLevel === self::LEVEL_REACHED) {
            $this->safelyCreate(
                $user,
                UserNotificationType::CREDIT_LIMIT_REACHED,
                'تم الوصول إلى الحد الائتماني',
                "وصل رصيد الزبون {$customer->name} إلى الحد الائتماني المحدد.",
                $this->data($customer, $resultingDebt, $limit, $transactionId)
            );

            return;
        }

        $percentage = $this->warningPercentage();

        $this->safelyCreate(
            $user,
            UserNotificationType::CREDIT_LIMIT_NEAR,
            'الاقتراب من الحد الائتماني',
            "وصل رصيد الزبون {$customer->name} إلى {$percentage}% من الحد الائتماني.",
            $this->data($customer, $resultingDebt, $limit, $transactionId)
        );
    }

    public function notifyExceededAttempt(
        User $user,
        Customer $customer,
        float $attemptedDebt
    ): void {
        $limit = (float) $customer->credit_limit;

        $this->safelyCreate(
            $user,
            UserNotificationType::CREDIT_LIMIT_EXCEEDED_ATTEMPT,
            'محاولة تجاوز الحد الائتماني',
            "تم رفض معاملة للزبون {$customer->name} لأنها تتجاوز الحد الائتماني.",
            $this->data($customer, $attemptedDebt, $limit)
        );
    }

    private function level(float $debt, float $limit): int
    {
        $debtInCents = (int) round($debt * 100);
        $limitInCents = (int) round($limit * 100);

        if ($debtInCents >= $limitInCents) {
            return self::LEVEL_REACHED;
        }

        $threshold = (int) round(
            $limitInCents * ($this->warningPercentage() / 100)
        );

        return $debtInCents >= $threshold
            ? self::LEVEL_NEAR
            : self::LEVEL_NORMAL;
    }

    private function warningPercentage(): int
    {
        $percentage = (int) config(
            'notifications.credit_limit_warning_percentage',
            80
        );

        return min(99, max(1, $percentage));
    }

    private function data(
        Customer $customer,
        float $debt,
        float $limit,
        ?int $transactionId = null
    ): array {
        return [
            'customer_id' => $customer->id,
            'transaction_id' => $transactionId,
            'debt' => number_format($debt, 2, '.', ''),
            'credit_limit' => number_format($limit, 2, '.', ''),
        ];
    }

    private function safelyCreate(
        User $user,
        UserNotificationType $type,
        string $title,
        string $message,
        array $data
    ): void {
        try {
            $this->notifications->create(
                $user,
                $type,
                $title,
                $message,
                $data
            );
        } catch (Throwable $exception) {
            Log::error('Failed to create a credit-limit notification.', [
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'],
                'notification_type' => $type->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
