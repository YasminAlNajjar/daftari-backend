<?php

namespace App\Services;

use App\Models\ReportCertificate;
use App\Models\ReportExport;
use Illuminate\Support\Str;
use RuntimeException;

class ReportCertificateService
{
    public function create(ReportExport $reportExport): array
    {
        if ($reportExport->certificate()->exists()) {
            throw new RuntimeException(
                'REPORT_CERTIFICATE_ALREADY_EXISTS'
            );
        }

        $verificationToken = Str::random(64);

        $verificationTokenHash = $this->hashToken(
            $verificationToken
        );

        $certificate = ReportCertificate::create([
            'user_id' => $reportExport->user_id,
            'report_export_id' => $reportExport->id,
            'verification_token_hash' => $verificationTokenHash,
            'file_hash' => null,
        ]);

        return [
            'certificate' => $certificate,

            'verification_token' => $verificationToken,

            'verification_url' => $this->generateVerificationUrl( $verificationToken ),
        ];
    }

    public function generateVerificationUrl(string $verificationToken): string 
    {
        return route(
            'reports.verify.page',
            [
                'token' =>$verificationToken,
            ]
        );
    }

    public function hashToken(string $verificationToken): string
    {
        return hash(
            'sha256',
            $verificationToken
        );
    }

    public function findByToken(string $verificationToken): ?ReportCertificate {

        $tokenHash = $this->hashToken(
            $verificationToken
        );

        return ReportCertificate::where(
            'verification_token_hash',
            $tokenHash
        )->first();
    }

    public function updateFileHash(ReportCertificate $certificate,string $filePath): ReportCertificate 
    {

        if (!is_file($filePath)) {
            throw new RuntimeException(
                'REPORT_FILE_NOT_FOUND'
            );
        }

        $fileHash = hash_file(
            'sha256',
            $filePath
        );

        if ($fileHash === false) {
            throw new RuntimeException(
                'REPORT_FILE_HASH_FAILED'
            );
        }

        $certificate->update([
            'file_hash' => $fileHash,
        ]);

        return $certificate->refresh();
    }

    public function verifyFile(ReportCertificate $certificate,string $filePath): bool 
    {

        if (!is_file($filePath)) {
            throw new RuntimeException(
                'REPORT_FILE_NOT_FOUND'
            );
        }

        if (!$certificate->file_hash) {
            throw new RuntimeException(
                'REPORT_FILE_HASH_NOT_AVAILABLE'
            );
        }

        $uploadedFileHash = hash_file(
            'sha256',
            $filePath
        );

        if ($uploadedFileHash === false) {
            throw new RuntimeException(
                'REPORT_FILE_HASH_FAILED'
            );
        }

        return hash_equals(
            $certificate->file_hash,
            $uploadedFileHash
        );
    }


}