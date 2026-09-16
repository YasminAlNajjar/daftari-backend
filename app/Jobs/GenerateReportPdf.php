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

class GenerateReportPdf implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $exportId,
        public string $verificationToken
    ) {
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
                $certificate
                    ->verification_token_hash,
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
        |
        | هذا الرابط سيدخل داخل QR الموجود في التقرير.
        |
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
        |
        | الـ Job لا يعرف:
        |
        | - date
        | - customer_id
        | - from_date
        | - to_date
        | - type
        | - filter
        | - sort
        |
        | كل هذه المسؤولية داخل ReportExportManager.
        |
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
        | Report Directory
        |--------------------------------------------------------------------------
        */

        $disk =
            Storage::disk('local');

        $directory =
            'report-exports';

        $disk->makeDirectory(
            $directory
        );

        /*
        |--------------------------------------------------------------------------
        | File Name
        |--------------------------------------------------------------------------
        |
        | أمثلة:
        |
        | daily_inventory-12.pdf
        | customer_statement-13.pdf
        | general_transactions-14.pdf
        | customer_balances-15.pdf
        |
        */

        $fileName =
            $export->report_type
            . '-'
            . $export->id
            . '.pdf';

        $relativePath =
            $directory
            . '/'
            . $fileName;

        $absolutePath =
            $disk->path(
                $relativePath
            );

        /*
        |--------------------------------------------------------------------------
        | mPDF Temp Directory
        |--------------------------------------------------------------------------
        */

        $tempDirectory =
            storage_path(
                'app/mpdf-temp'
            );

        File::ensureDirectoryExists(
            $tempDirectory
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
                $tempDirectory,
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
        | Save Final PDF
        |--------------------------------------------------------------------------
        |
        | مهم:
        | الملف هنا يحتوي بالفعل على QR.
        |
        */

        $mpdf->Output(
            $absolutePath,
            Destination::FILE
        );

        /*
        |--------------------------------------------------------------------------
        | Calculate Final File Hash
        |--------------------------------------------------------------------------
        |
        | الـ SHA-256 يجب أن يحسب بعد إضافة QR
        | وبعد إنشاء النسخة النهائية من PDF.
        |
        */

        $certificateService
            ->updateFileHash(
                $certificate,
                $absolutePath
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
    }


    public function failed(Throwable $exception): void
{
    $export = ReportExport::find(
        $this->exportId
    );

    if (!$export) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Incomplete File
    |--------------------------------------------------------------------------
    |
    | إذا تم إنشاء ملف PDF ثم حدث الخطأ بعد ذلك
    | مثل فشل حساب الـ hash، نحذف الملف غير المكتمل.
    |
    */

    if ($export->temporary_file_path) {
        Storage::disk('local')->delete(
            $export->temporary_file_path
        );
    } else {
        $possiblePath =
            'report-exports/'
            . $export->report_type
            . '-'
            . $export->id
            . '.pdf';

        Storage::disk('local')->delete(
            $possiblePath
        );
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
}
}