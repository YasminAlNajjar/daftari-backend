<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Requests\Report\CreateReportExportRequest;
use App\Jobs\GenerateReportPdf;
use App\Models\Customer;
use App\Models\ReportExport;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Services\ReportCertificateService;
use Illuminate\Support\Facades\Storage;

class ReportExportController extends Controller
{

    public function __construct(
        private ReportCertificateService $certificateService
    ) {
    }   

    public function store(CreateReportExportRequest $request): JsonResponse 
    {
        $validated = $request->validated();

        $userId = $request->user()->id;
        $reportType = $validated['report_type'];

        /*
        |--------------------------------------------------------------------------
        | Customer Ownership Check
        |--------------------------------------------------------------------------
        */

        if (
            $reportType ===
            ReportExport::TYPE_CUSTOMER_STATEMENT
        ) {
            $customerExists = Customer::where(
                'user_id',
                $userId
            )
                ->where(
                    'id',
                    $validated['customer_id']
                )
                ->exists();

            if (!$customerExists) {
                return ApiResponse::error(
                    'الزبون غير موجود.',
                    'CUSTOMER_NOT_FOUND',
                    404
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build Report Parameters
        |--------------------------------------------------------------------------
        */

        $parameters = match ($reportType) {

            ReportExport::TYPE_DAILY_INVENTORY => [
                'date' => $validated['date'],
            ],

            ReportExport::TYPE_CUSTOMER_STATEMENT => [
                'customer_id' =>
                    (int) $validated['customer_id'],

                'from_date' =>
                    $validated['from_date'],

                'to_date' =>
                    $validated['to_date'],

                'type' =>
                    $validated['type'] ?? 'all',
            ],

            ReportExport::TYPE_GENERAL_TRANSACTIONS => [
                'from_date' =>
                    $validated['from_date'],

                'to_date' =>
                    $validated['to_date'],

                'type' =>
                    $validated['type'] ?? 'all',
            ],

            ReportExport::TYPE_CUSTOMER_BALANCES => [
                'filter' =>
                    $validated['filter'] ?? 'all',

                'sort' =>
                    $validated['sort']
                    ?? 'most_indebted',
            ],

            default => throw new \RuntimeException(
            'UNSUPPORTED_REPORT_TYPE'
            ),
        };

        /*
|--------------------------------------------------------------------------
| Create Export + Certificate
|--------------------------------------------------------------------------
*/

    $result = DB::transaction(
        function () use ($userId,$reportType,$parameters) 
        {
            $export = ReportExport::create([
                'user_id' => $userId,
                'report_type' => $reportType,
                'parameters' => $parameters,
                'status' => ReportExport::STATUS_PENDING,
            ]);

            $certificateData =
                $this->certificateService->create(
                    $export
                );

            return [
                'export' => $export,
                'certificate_data' => $certificateData,
            ];
        }
    );

    $export = $result['export'];

    $certificateData = $result['certificate_data'];

/*
|--------------------------------------------------------------------------
| Prepare Response First
|--------------------------------------------------------------------------
|
| نجهز الـ Response قبل إرسال الـ Job.
| إذا حدث خطأ هنا، لن يتم إنشاء Job.
|
*/

    $response = ApiResponse::success(
        'تم بدء إنشاء ملف PDF.',
        [
            'export_id' => $export->id,
            'status' => $export->status,

        // مؤقتًا للاختبار فقط
            // 'verification_token' =>
            //      $certificateData['verification_token'],

            // 'verification_url' =>
            //     $certificateData['verification_url'],
        ],
        202
    );

/*
|--------------------------------------------------------------------------
| Dispatch Job
|--------------------------------------------------------------------------
|
| هذه آخر عملية قبل إرجاع الـ Response.
|
*/

    GenerateReportPdf::dispatch(
        $export->id,
        $certificateData['verification_token']
    );

/*
|--------------------------------------------------------------------------
| Return Response
|--------------------------------------------------------------------------
*/

    return $response;




    }

    public function show(int $id): JsonResponse 
    {
        $export = ReportExport::where(
            'user_id',
            request()->user()->id
        )->find($id);

        if (!$export) {
            return ApiResponse::error(
                'عملية التصدير غير موجودة.',
                'REPORT_EXPORT_NOT_FOUND',
                404
            );
        }

        return ApiResponse::success(
            'تم جلب حالة التصدير بنجاح.',
            [
                'export_id' => $export->id,
                'report_type' => $export->report_type,
                'status' => $export->status,
                'completed_at' => $export->completed_at,
                'expires_at' => $export->expires_at,
            ]
        );
    }

    public function download(Request $request,ReportExport $export) 
    {

        if (
            (int)$export->user_id !==
            (int)$request->user()->id
        ) {
            return ApiResponse::error(
                'غير مصرح لك بتنزيل هذا التقرير.',
                'FORBIDDEN',
                403
            );
        }


        if (
            $export->expires_at &&
            $export->expires_at->isPast()
        ) {
            return ApiResponse::error(
                'انتهت صلاحية رابط تنزيل التقرير.',
                'REPORT_EXPORT_EXPIRED',
                410
            );
        }

        if (
            $export->status !==
            ReportExport::STATUS_COMPLETED
        ) {
            return ApiResponse::error(
                'التقرير غير جاهز للتنزيل.',
                'REPORT_NOT_READY',
                409
            );
        }

        $disk = Storage::disk('local');

        $filePath = $export->temporary_file_path;

        if ( !$filePath || !$disk->exists($filePath)) 
        {
            return ApiResponse::error(
                'ملف PDF غير متوفر.',
                'REPORT_FILE_NOT_FOUND',
                404
            );
        }

        $fileName =
            $export->report_type
            . '-'
            . $export->id
            . '.pdf';

        return response()->download(
            $disk->path($filePath),
            $fileName,
            [
                'Content-Type' =>
                    'application/pdf',
            ]
        );
    }


}
