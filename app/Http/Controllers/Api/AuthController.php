<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    private const TOKEN_EXPIRES_IN = 2592000;
    private const ONBOARDING_EXPIRES_IN = 600;

    /**
     * إرسال OTP.
     */
    public function sendOtp(
        SendOtpRequest $request,
        OtpService $otpService
    ): JsonResponse {
        $phone = $request->validated('phone');

        $result = $otpService->send($phone);

        if (! $result['success']) {
            return match ($result['code']) {
                'OTP_RESEND_TOO_SOON' => ApiResponse::error(
                    'يرجى الانتظار قبل طلب رمز جديد.',
                    'OTP_RESEND_TOO_SOON',
                    429,
                    [
                        'retry_after' => $result['retry_after'],
                    ]
                ),

                'OTP_SEND_LIMIT_EXCEEDED' => ApiResponse::error(
                    'تم تجاوز الحد المسموح لإرسال رموز التحقق.',
                    'OTP_SEND_LIMIT_EXCEEDED',
                    429,
                    [
                        'retry_after' => $result['retry_after'],
                    ]
                ),

                'WHATSAPP_PROVIDER_UNAVAILABLE' => ApiResponse::error(
                    'تعذر إرسال رمز التحقق حاليًا.',
                    'WHATSAPP_PROVIDER_UNAVAILABLE',
                    503
                ),

                default => ApiResponse::error(
                    'حدث خطأ أثناء إرسال رمز التحقق.',
                    'INTERNAL_SERVER_ERROR',
                    500
                ),
            };
        }

        return ApiResponse::success(
            'تم إرسال رمز التحقق.',
            [
                'expires_in' => $result['expires_in'],
                'resend_after' => $result['resend_after'],
            ]
        );
    }

    /**
     * التحقق من OTP.
     */
   public function verifyOtp(
    VerifyOtpRequest $request,
    OtpService $otpService
): JsonResponse {
    $data = $request->validated();

    $result = $otpService->verify(
        $data['phone'],
        $data['code']
    );

    /*
     * فشل التحقق من OTP.
     */
    if (! $result['success']) {
        return match ($result['code']) {

            'OTP_NOT_FOUND' => ApiResponse::error(
                'لا يوجد رمز تحقق فعال لهذا الرقم.',
                'OTP_NOT_FOUND',
                404
            ),

            'OTP_EXPIRED' => ApiResponse::error(
                'انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.',
                'OTP_EXPIRED',
                410
            ),

            'OTP_INVALID' => ApiResponse::error(
                'رمز التحقق غير صحيح.',
                'OTP_INVALID',
                401,
                [
                    'attempts_remaining'
                        => $result['attempts_remaining'],
                ]
            ),

            'OTP_MAX_ATTEMPTS' => ApiResponse::error(
                'تم تجاوز عدد محاولات التحقق. اطلب رمزًا جديدًا.',
                'OTP_MAX_ATTEMPTS',
                429,
                [
                    'attempts_remaining' => 0,
                ]
            ),

            default => ApiResponse::error(
                'حدث خطأ أثناء التحقق من رمز التحقق.',
                'INTERNAL_SERVER_ERROR',
                500
            ),
        };
    }

    /*
     * الكود صحيح.
     * نبحث عن المستخدم باستخدام رقم الهاتف.
     */
    $user = User::query()
        ->where('phone', $data['phone'])
        ->first();

    /*
     * المستخدم موجود مسبقًا.
     */
    if ($user !== null) {
        $token = $user->createToken(
            'mobile-token',
            ['*'],
            now()->addDays(30)
        );

        return ApiResponse::success(
            'تم تسجيل الدخول بنجاح.',
            [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => self::TOKEN_EXPIRES_IN,

                'is_new_user' => false,

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'address' => $user->address,
                    'business_activity_type'
                        => $user->business_activity_type,
                ],
            ]
        );
    }

    /*
     * المستخدم جديد.
     *
     * لا ننشئ User الآن.
     * نعطي Flutter توكن مؤقت لإكمال الملف الشخصي.
     */
    $onboardingToken = Crypt::encryptString(
        json_encode([
            'phone' => $data['phone'],

            'expires_at' => now()
                ->addSeconds(self::ONBOARDING_EXPIRES_IN)
                ->timestamp,
        ])
    );

    return ApiResponse::success(
        'تم التحقق من رقم الهاتف. يرجى إكمال الملف الشخصي.',
        [
            'is_new_user' => true,

            'onboarding_token' => $onboardingToken,

            'expires_in' => self::ONBOARDING_EXPIRES_IN,
        ]
    );
}
    /**
     * إكمال ملف المستخدم الجديد.
     */
    public function completeProfile(
    CompleteProfileRequest $request
): JsonResponse {
    $data = $request->validated();

    /*
     * فك Onboarding Token.
     */
    try {
        $payload = json_decode(
            Crypt::decryptString($data['onboarding_token']),
            true
        );
    } catch (DecryptException $exception) {
        report($exception);

        return ApiResponse::error(
            'رمز إكمال الملف الشخصي غير صالح.',
            'INVALID_ONBOARDING_TOKEN',
            401
        );
    }

    /*
     * التأكد من بنية Token.
     */
    if (
        ! isset(
            $payload['phone'],
            $payload['expires_at']
        )
    ) {
        return ApiResponse::error(
            'رمز إكمال الملف الشخصي غير صالح.',
            'INVALID_ONBOARDING_TOKEN',
            401
        );
    }

    /*
     * التأكد من أن Token لم تنته صلاحيته.
     */
    if (now()->timestamp > $payload['expires_at']) {
        return ApiResponse::error(
            'انتهت صلاحية رمز إكمال الملف الشخصي.',
            'ONBOARDING_TOKEN_EXPIRED',
            401
        );
    }

    /*
     * منع إنشاء حساب آخر لنفس رقم الهاتف.
     */
    if (
        User::query()
            ->where('phone', $payload['phone'])
            ->exists()
    ) {
        return ApiResponse::error(
            'يوجد حساب مرتبط بهذا الرقم مسبقًا.',
            'USER_ALREADY_EXISTS',
            409
        );
    }

    /*
     * إنشاء المستخدم بعد إكمال بياناته.
     */
    $user = User::create([
        'name' => $data['name'],
        'phone' => $payload['phone'],
        'email' => $data['email'],
        'address' => $data['address'],
        'business_activity_type'
            => $data['business_activity_type'],
    ]);

    /*
     * إنشاء Access Token لمدة 30 يومًا.
     */
    $token = $user->createToken(
        'mobile-token',
        ['*'],
        now()->addDays(30)
    );

    /*
     * إرجاع بيانات المستخدم مع Access Token.
     */
    return ApiResponse::success(
        'تم إنشاء الحساب بنجاح.',
        [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_EXPIRES_IN,
            'is_new_user' => true,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'address' => $user->address,
                'business_activity_type'
                    => $user->business_activity_type,
            ],
        ],
        201
    );
}
    /**
     * تسجيل الخروج.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return ApiResponse::success(
            'تم تسجيل الخروج بنجاح.'
        );
    }

    /**
     * بيانات المستخدم الحالي.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(
            'تم جلب بيانات المستخدم بنجاح.',
            [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'address' => $user->address,
                'business_activity_type'
                    => $user->business_activity_type,
            ]
        );
    }
}