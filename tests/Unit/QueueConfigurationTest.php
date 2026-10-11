<?php

namespace Tests\Unit;

use App\Jobs\GenerateReportPdf;
use App\Jobs\SendPushNotification;
use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_reports_and_notifications_use_separate_queues(): void
    {
        config([
            'reports.queue' => 'reports-test',
            'notifications.queue' => 'notifications-test',
        ]);

        $reportJob = new GenerateReportPdf(1, 'verification-token');
        $notificationJob = new SendPushNotification(1, 1);

        $this->assertSame('reports-test', $reportJob->queue);
        $this->assertSame('notifications-test', $notificationJob->queue);
    }

    public function test_report_job_has_bounded_retries_and_timeout(): void
    {
        $job = new GenerateReportPdf(1, 'verification-token');

        $this->assertSame(2, $job->tries);
        $this->assertSame(300, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame([60], $job->backoff());
        $this->assertGreaterThan(
            $job->timeout,
            config('queue.connections.database.retry_after')
        );
    }
}
