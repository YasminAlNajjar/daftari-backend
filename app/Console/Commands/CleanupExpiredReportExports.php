<?php

namespace App\Console\Commands;

use App\Models\ReportExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredReportExports extends Command
{
    protected $signature =
        'reports:cleanup-expired';

    protected $description =
        'Delete expired report PDF files';

    public function handle(): int
    {
        $disk =
            Storage::disk(
                config(
                    'filesystems.reports_disk',
                    'local'
                )
            );

        $deletedCount = 0;

        ReportExport::query()
            ->where(
                'status',
                ReportExport::STATUS_COMPLETED
            )
            ->whereNotNull(
                'expires_at'
            )
            ->where(
                'expires_at',
                '<=',
                now()
            )
            ->whereNotNull(
                'temporary_file_path'
            )
            ->chunkById(
                100,
                function ($exports) use (
                    $disk,
                    &$deletedCount
                ) {
                    foreach ($exports as $export) {

                        /** @var ReportExport $export */

                        $disk->delete(
                            $export->temporary_file_path
                        );

                        $export->update([
                            'temporary_file_path' =>
                                null,
                        ]);

                        $deletedCount++;
                    }
                }
            );

        $this->info(
            "Deleted {$deletedCount} expired report files."
        );

        return self::SUCCESS;
    }
}