<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Helpers\ApiResponse;
use App\Services\ReportCertificateService;
use Illuminate\Http\JsonResponse;

class ReportVerificationController extends Controller
{
    public function __construct(
        private ReportCertificateService $certificateService
    ) {
    }

    public function verify(string $token): JsonResponse
    {
        $certificate = $this->certificateService
            ->findByToken($token);

        if (!$certificate) {
            return ApiResponse::error(
                'Invalid or unknown report verification token.',
                'INVALID_VERIFICATION_TOKEN',
                404
            );
        }

        $certificate->load([
            'reportExport',
            'user',
        ]);

        return ApiResponse::success(
            'Report certificate verified successfully.',
            [
                'certificate_id' => $certificate->id,

                'report_export_id' =>
                    $certificate->report_export_id,

                'user_id' =>
                    $certificate->user_id,

                'file_hash' =>
                    $certificate->file_hash,

                'issued_at' =>
                    $certificate->created_at,

                'report' => [
                    'id' =>
                        $certificate->reportExport->id,
                ],
            ]
        );
    }
}
