<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\ReportCertificateService;
use App\Services\Reports\ReportExportManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Throwable;
use App\Enums\UserNotificationType;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class GenerateReportPdf implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $exportId,
        public string $verificationToken
    ) {
        $this->onQueue(config('reports.queue', 'reports'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60];
    }

    public function handle(
        ReportExportManager $exportManager,
        ReportCertificateService $certificateService
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Load Export
        |--------------------------------------------------------------------------
        */

        $export = ReportExport::query()
            ->with('certificate')
            ->find($this->exportId);

        if (!$export) {
            throw new RuntimeException(
                'REPORT_EXPORT_NOT_FOUND'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load Certificate
        |--------------------------------------------------------------------------
        */

        $certificate =
            $export->certificate;

        if (!$certificate) {
            throw new RuntimeException(
                'REPORT_CERTIFICATE_NOT_FOUND'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Certificate Token
        |--------------------------------------------------------------------------
        |
        | الـ raw token موجود فقط داخل الـ encrypted Job.
        | نقارنه مع hash المخزن في قاعدة البيانات.
        |
        */

        $receivedTokenHash =
            $certificateService->hashToken(
                $this->verificationToken
            );

        if (
            !hash_equals(
                $certificate->verification_token_hash,
                $receivedTokenHash
            )
        ) {
            throw new RuntimeException(
                'INVALID_VERIFICATION_TOKEN'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verification URL
        |--------------------------------------------------------------------------
        */

        $verificationUrl =
            $certificateService
                ->generateVerificationUrl(
                    $this->verificationToken
                );

        /*
        |--------------------------------------------------------------------------
        | Prepare Report
        |--------------------------------------------------------------------------
        */

        $prepared =
            $exportManager->prepare(
                $export
            );

        $report =
            $prepared['report'];

        $view =
            $prepared['view'];

        /*
        |--------------------------------------------------------------------------
        | Render Blade
        |--------------------------------------------------------------------------
        */

        $html = view(
            $view,
            [
                'report' =>
                    $report,

                'verificationUrl' =>
                    $verificationUrl,

                'certificate' =>
                    $certificate,

                'export' =>
                    $export,
            ]
        )->render();

        /*
        |--------------------------------------------------------------------------
        | Reports Storage Disk
        |--------------------------------------------------------------------------
        |
        | Local:
        | REPORTS_DISK غير موجود → local
        |
        | Railway:
        | REPORTS_DISK=reports → Railway Bucket
        |
        */

        $diskName =
            config(
                'filesystems.reports_disk',
                'local'
            );

        $disk =
            Storage::disk(
                $diskName
            );

        /*
        |--------------------------------------------------------------------------
        | Final File Path
        |--------------------------------------------------------------------------
        */

        $directory =
            'report-exports';

        $fileName =
            $export->report_type
            . '-'
            . $export->id
            . '.pdf';

        $relativePath =
            $directory
            . '/'
            . $fileName;

        /*
        |--------------------------------------------------------------------------
        | Temporary Local PDF
        |--------------------------------------------------------------------------
        |
        | mPDF يحتاج مسارًا محليًا فعليًا.
        | لذلك ننشئ PDF مؤقتًا داخل Worker،
        | ثم نرفعه إلى الـ storage disk.
        |
        */

        $tempReportDirectory =
            storage_path(
                'app/report-temp'
            );

        File::ensureDirectoryExists(
            $tempReportDirectory
        );

        $tempPdfPath =
            $tempReportDirectory
            . DIRECTORY_SEPARATOR
            . $fileName;

        /*
        |--------------------------------------------------------------------------
        | mPDF Temp Directory
        |--------------------------------------------------------------------------
        */

        $mpdfTempDirectory =
            storage_path(
                'app/mpdf-temp'
            );

        File::ensureDirectoryExists(
            $mpdfTempDirectory
        );

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $mpdf = new Mpdf([
            'mode' =>
                'utf-8',

            'format' =>
                'A4',

            'tempDir' =>
                $mpdfTempDirectory,
        ]);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf->SetDirectionality(
            'rtl'
        );

        $mpdf->WriteHTML(
            $html
        );

        /*
        |--------------------------------------------------------------------------
        | Save Temporary Final PDF
        |--------------------------------------------------------------------------
        |
        | الملف يحتوي هنا بالفعل على QR.
        |
        */

        $mpdf->Output(
            $tempPdfPath,
            Destination::FILE
        );

        if (!is_file($tempPdfPath)) {
            throw new RuntimeException(
                'REPORT_FILE_GENERATION_FAILED'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Final File Hash
        |--------------------------------------------------------------------------
        |
        | نحسب SHA-256 على النسخة النهائية بعد إضافة QR
        | وقبل رفع الملف إلى الـ Bucket.
        |
        */

        $certificateService
            ->updateFileHash(
                $certificate,
                $tempPdfPath
            );

        /*
        |--------------------------------------------------------------------------
        | Upload Final PDF
        |--------------------------------------------------------------------------
        */

        $fileStream =
            fopen(
                $tempPdfPath,
                'rb'
            );

        if ($fileStream === false) {
            throw new RuntimeException(
                'REPORT_FILE_OPEN_FAILED'
            );
        }

        try {
            $uploaded =
                $disk->put(
                    $relativePath,
                    $fileStream
                );
        } finally {
            fclose(
                $fileStream
            );
        }

        if (!$uploaded) {
            throw new RuntimeException(
                'REPORT_FILE_UPLOAD_FAILED'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Uploaded File Exists
        |--------------------------------------------------------------------------
        */

        if (
            !$disk->exists(
                $relativePath
            )
        ) {
            throw new RuntimeException(
                'REPORT_FILE_UPLOAD_NOT_FOUND'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Temporary Local File
        |--------------------------------------------------------------------------
        */

        File::delete(
            $tempPdfPath
        );

        /*
        |--------------------------------------------------------------------------
        | Complete Export
        |--------------------------------------------------------------------------
        */

        $export->update([
            'status' =>
                ReportExport::STATUS_COMPLETED,

            'temporary_file_path' =>
                $relativePath,

            'completed_at' =>
                now(),

            'expires_at' =>
                now()->addHours(24),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create Report Ready Notification
        |--------------------------------------------------------------------------
        */

        try {
                $user = User::find( $export->user_id );

                if ($user) 
                {
                    $notificationService = app( NotificationService::class );

                    $notificationService->create(
                            $user,
                            UserNotificationType::REPORT_READY,
                            'التقرير جاهز',
                            'تم إنشاء التقرير بنجاح وأصبح جاهزًا للتنزيل.',
                            [
                                'export_id' => $export->id,

                                'report_type' => $export->report_type,
                            ]
                    );
                 }
        } catch (Throwable $notificationException) 
            {
                Log::error(
                    'Failed to create REPORT_READY notification.',
                    [
                        'export_id' => $export->id,

                        'error' => $notificationException->getMessage(),
                    ]
                );
            }
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job
    |--------------------------------------------------------------------------
    */

    public function failed(
        Throwable $exception
    ): void {

        $export =
            ReportExport::query()
                ->with('certificate')
                ->find(
                    $this->exportId
                );

        if (!$export) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Reports Storage Disk
        |--------------------------------------------------------------------------
        */

        $diskName =
            config(
                'filesystems.reports_disk',
                'local'
            );

        $disk =
            Storage::disk(
                $diskName
            );

        /*
        |--------------------------------------------------------------------------
        | Possible Remote File Path
        |--------------------------------------------------------------------------
        */

        $possiblePath =
            $export->temporary_file_path
            ?? (
                'report-exports/'
                . $export->report_type
                . '-'
                . $export->id
                . '.pdf'
            );

        /*
        |--------------------------------------------------------------------------
        | Delete Uploaded / Incomplete File
        |--------------------------------------------------------------------------
        */

        if (
            $disk->exists(
                $possiblePath
            )
        ) {
            $disk->delete(
                $possiblePath
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Temporary Local File
        |--------------------------------------------------------------------------
        */

        $tempPdfPath =
            storage_path(
                'app/report-temp/'
                . $export->report_type
                . '-'
                . $export->id
                . '.pdf'
            );

        if (
            File::exists(
                $tempPdfPath
            )
        ) {
            File::delete(
                $tempPdfPath
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clear Certificate File Hash
        |--------------------------------------------------------------------------
        |
        | إذا فشل رفع الملف بعد حساب الـ hash،
        | لا نريد الاحتفاظ بـ hash لملف غير متاح.
        |
        */

        if ($export->certificate) {
            $export->certificate->update([
                'file_hash' =>
                    null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Export As Failed
        |--------------------------------------------------------------------------
        */

        $export->update([
            'status' =>
                ReportExport::STATUS_FAILED,

            'temporary_file_path' =>
                null,

            'completed_at' =>
                null,

            'expires_at' =>
                null,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Create Report Failed Notification
        |--------------------------------------------------------------------------
        */

        try {
                $user = User::find( $export->user_id );

                if ($user) 
                {
                    $notificationService =
                    app( NotificationService::class);
 
                    $notificationService->create(
                            $user,
                            UserNotificationType::REPORT_FAILED,
                            'فشل إنشاء التقرير',
                            'تعذر إنشاء التقرير. يرجى المحاولة مرة أخرى.',
                            [
                                'export_id' => $export->id,

                                'report_type' => $export->report_type,
                            ]
                    );
                }
            } catch (Throwable $notificationException) 
                {
                    Log::error(
                        'Failed to create REPORT_FAILED notification.',
                        [
                            'export_id' => $export->id,

                            'error' => $notificationException->getMessage(),
                        ]
                    );
                }       
    }



}
