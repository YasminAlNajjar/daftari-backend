<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportCertificateService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class ReportVerificationController extends Controller
{
    public function __construct(
        private ReportCertificateService $certificateService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Show Verification Page
    |--------------------------------------------------------------------------
    */

    public function show(string $token): View
    {
        $certificate =
            $this->certificateService
                ->findByToken($token);

        /*
        |--------------------------------------------------------------------------
        | Invalid Token
        |--------------------------------------------------------------------------
        */

        if (!$certificate) {
            return view(
                'reports.verification',
                [
                    'validCertificate' => false,
                    'certificate' => null,
                    'token' => $token,
                    'fileVerification' => null,
                ]
            );
        }

        $certificate->load(
            'reportExport'
        );

        /*
        |--------------------------------------------------------------------------
        | Valid Certificate
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.verification',
            [
                'validCertificate' => true,
                'certificate' => $certificate,
                'token' => $token,
                'fileVerification' => null,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Uploaded PDF
    |--------------------------------------------------------------------------
    */

    public function verifyFile(
        Request $request,
        string $token
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Find Certificate
        |--------------------------------------------------------------------------
        */

        $certificate =
            $this->certificateService
                ->findByToken($token);

        if (!$certificate) {
            return view(
                'reports.verification',
                [
                    'validCertificate' => false,
                    'certificate' => null,
                    'token' => $token,
                    'fileVerification' => null,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate PDF
        |--------------------------------------------------------------------------
        */

        $request->validate(
            [
                'report_file' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10240',
                ],
            ],
            [
                'report_file.required' =>
                    'يرجى اختيار ملف PDF.',

                'report_file.file' =>
                    'الملف المرفوع غير صالح.',

                'report_file.mimes' =>
                    'يجب أن يكون الملف بصيغة PDF.',

                'report_file.max' =>
                    'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Verify File Integrity
        |--------------------------------------------------------------------------
        */

        $isValid =
            $this->certificateService
                ->verifyFile(
                    $certificate,
                    $request
                        ->file('report_file')
                        ->getRealPath()
                );

        $certificate->load(
            'reportExport'
        );

        /*
        |--------------------------------------------------------------------------
        | Return Same Page With Result
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.verification',
            [
                'validCertificate' => true,
                'certificate' => $certificate,
                'token' => $token,

                'fileVerification' => $isValid
                    ? 'valid'
                    : 'modified',
            ]
        );
    }
}