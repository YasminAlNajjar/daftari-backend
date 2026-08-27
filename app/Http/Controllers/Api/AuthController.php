<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    private const TOKEN_EXPIRES_IN = 2592000; // 30 days
    private const ONBOARDING_TOKEN_EXPIRES_IN = 600; // 10 minutes

    public function __construct(
        private readonly OtpService $otpService
    ) {
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse {
        $data = $request->validated();

        $result = $this->otpService->send(
            $data['email']
        );

        if (! $result['success']) {
            $status = match ($result['code']) {
                'OTP_RESEND_TOO_SOON' => 429,
                'OTP_SEND_LIMIT_EXCEEDED' => 429,
                'EMAIL_PROVIDER_UNAVAILABLE' => 503,
                default => 500,
            };

            $extra = [];

            if (isset($result['retry_after'])) {
                $extra['retry_after'] = $result['retry_after'];
            }

            return ApiResponse::error(
                $result['message'],
                $result['code'],
                $status,
                $extra
            );
        }

        return ApiResponse::success(
            'تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
            [
                'expires_in' => $result['expires_in'],
                'resend_after' => $result['resend_after'],
            ]
        );
    }

    public function verifyOtp(
        VerifyOtpRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $result = $this->otpService->verify(
            $data['email'],
            $data['code']
        );

        if (! $result['success']) {
            $status = match ($result['code']) {
                'OTP_NOT_FOUND' => 404,
                'OTP_EXPIRED',
                'OTP_INVALID',
                'OTP_MAX_ATTEMPTS' => 422,
                default => 500,
            };

            $extra = [];

            if (isset($result['attempts_remaining'])) {
                $extra['attempts_remaining']
                    = $result['attempts_remaining'];
            }

            return ApiResponse::error(
                $result['message'],
                $result['code'],
                $status,
                $extra
            );
        }

        /*
         * البحث عن المستخدم بواسطة الإيميل
         */
        $user = User::query()
            ->where('email', $result['email'])
            ->first();

        /*
         * Existing User
         */
        if ($user) {
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
         * New User
         *
         * نخزن الإيميل الموثق داخل
         * onboarding token.
         */
        $onboardingToken = Crypt::encryptString(
            json_encode([
                'email' => $result['email'],
                'expires_at' => now()
                    ->addSeconds(
                        self::ONBOARDING_TOKEN_EXPIRES_IN
                    )
                    ->timestamp,
            ])
        );

        return ApiResponse::success(
            'تم التحقق من البريد الإلكتروني بنجاح. يرجى إكمال بيانات الحساب.',
            [
                'is_new_user' => true,
                'onboarding_token' => $onboardingToken,
                'expires_in'
                    => self::ONBOARDING_TOKEN_EXPIRES_IN,
            ]
        );
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse {
        $data = $request->validated();

        try {
            $payload = json_decode(
                Crypt::decryptString(
                    $data['onboarding_token']
                ),
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
         * الآن نتحقق من email.
         */
        if (
            ! isset(
                $payload['email'],
                $payload['expires_at']
            )
        ) {
            return ApiResponse::error(
                'رمز إكمال الملف الشخصي غير صالح.',
                'INVALID_ONBOARDING_TOKEN',
                401
            );
        }

        if (
            now()->timestamp >
            $payload['expires_at']
        ) {
            return ApiResponse::error(
                'انتهت صلاحية رمز إكمال الملف الشخصي.',
                'ONBOARDING_TOKEN_EXPIRED',
                401
            );
        }

        /*
         * منع إنشاء حساب ثانٍ بنفس الإيميل.
         */
        if (
            User::query()
                ->where('email', $payload['email'])
                ->exists()
        ) {
            return ApiResponse::error(
                'يوجد حساب مرتبط بهذا البريد الإلكتروني مسبقًا.',
                'USER_ALREADY_EXISTS',
                409
            );
        }

        /*
         * إنشاء المستخدم.
         *
         * email يأتي من onboarding token.
         */
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $payload['email'],
            'address' => $data['address'],
            'business_activity_type'
                => $data['business_activity_type'],
        ]);

        $token = $user->createToken(
            'mobile-token',
            ['*'],
            now()->addDays(30)
        );

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

    public function me(Request $request ): JsonResponse {
        $user = $request->user();

        return ApiResponse::success(
            'تم جلب بيانات المستخدم بنجاح.',
            [
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

    public function logout(Request $request ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return ApiResponse::success(
            'تم تسجيل الخروج بنجاح.'
        );
    }
}