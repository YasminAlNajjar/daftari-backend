<?php

namespace App\Console\Commands;

use App\Enums\UserNotificationType;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendDailyDataReminders extends Command
{
    protected $signature = 'notifications:send-daily-reminders';

    protected $description = 'Remind users who have not entered any transactions today';

    public function handle(NotificationService $notifications): int
    {
        $timezone = config('notifications.timezone', 'Asia/Hebron');
        $localDate = CarbonImmutable::now($timezone);
        $startUtc = $localDate->startOfDay()->utc();
        $endUtc = $localDate->endOfDay()->utc();
        $sent = 0;

        User::query()
            ->whereDoesntHave('transactions', fn ($query) => $query->whereBetween('created_at', [$startUtc, $endUtc])
            )
            ->chunkById(200, function ($users) use (
                $notifications,
                $localDate,
                &$sent
            ) {
                foreach ($users as $user) {
                    $notification = $notifications->create(
                        $user,
                        UserNotificationType::DAILY_DATA_REMINDER,
                        'تذكير بإدخال بيانات اليوم',
                        'لم تسجل أي معاملات اليوم. لا تنسَ تحديث بياناتك.',
                        [
                            'date' => $localDate->toDateString(),
                        ],
                        "daily-data-reminder:{$localDate->toDateString()}"
                    );

                    if ($notification->wasRecentlyCreated) {
                        $sent++;
                    }
                }
            });

        $this->info("Created {$sent} daily reminder notifications.");

        return self::SUCCESS;
    }
}
