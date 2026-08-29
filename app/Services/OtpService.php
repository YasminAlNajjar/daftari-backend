<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Throwable;

class OtpService
{
    private const OTP_EXPIRES_IN = 120;
    private const RESEND_AFTER = 60;
    private const MAX_SENDS = 3;
    private const MAX_ATTEMPTS = 3;
    private const BLOCK_MINUTES = 60;

    public function __construct(
        private readonly EmailService $emailService
    ) {
    }

    public function send(string $email): array
    {
        $now = now();

        $lastOtp = OtpCode::query()
            ->where('email', $email)
            ->latest('id')
            ->first();

        /*
         * إذا كان الإيميل محظورًا.
         */
        if ($lastOtp?->isBlocked()) {
            return [
                'success' => false,
                'code' => 'OTP_SEND_LIMIT_EXCEEDED',
                'message' => 'تم تجاوز الحد المسموح لإرسال رمز التحقق.',
                'retry_after' => $now->diffInSeconds(
                    $lastOtp->blocked_until
                ),
            ];
        }

        /*
         * منع إعادة الإرسال قبل مرور 60 ثانية.
         */
        if (
            $lastOtp?->last_sent_at &&
            $lastOtp->last_sent_at
                ->copy()
                ->addSeconds(self::RESEND_AFTER)
                ->isFuture()
        ) {
            return [
                'success' => false,
                'code' => 'OTP_RESEND_TOO_SOON',
                'message' => 'يرجى الانتظار قبل طلب رمز تحقق جديد.',
                'retry_after' => $now->diffInSeconds(
                    $lastOtp->last_sent_at
                        ->copy()
                        ->addSeconds(self::RESEND_AFTER)
                ),
            ];
        }

        /*
         * حساب عدد مرات الإرسال داخل النافذة الحالية.
         */
        $sendCount = 1;
        $sendWindowStartedAt = $now;

        if (
            $lastOtp?->send_window_started_at &&
            $lastOtp->send_window_started_at
                ->copy()
                ->addHour()
                ->isFuture()
        ) {
            $sendCount = $lastOtp->send_count + 1;
            $sendWindowStartedAt = $lastOtp->send_window_started_at;
        }

        /*
         * حماية إضافية.
         */
        if ($sendCount > self::MAX_SENDS) {
            return [
                'success' => false,
                'code' => 'OTP_SEND_LIMIT_EXCEEDED',
                'message' => 'تم تجاوز الحد المسموح لإرسال رمز التحقق.',
                'retry_after' => self::BLOCK_MINUTES * 60,
            ];
        }

        /*
         * الإرسال الثالث مسموح،
         * وبعده يبدأ الحظر لمدة ساعة.
         */
        $blockedUntil = null;

        if ($sendCount === self::MAX_SENDS) {
            $blockedUntil = $now
                ->copy()
                ->addMinutes(self::BLOCK_MINUTES);
        }

        /*
         * إرسال OTP عبر البريد.
         */
        $otp = (string) random_int(100000, 999999);

        try {
            $this->emailService->sendOtp($email,$otp);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'code' => 'EMAIL_PROVIDER_UNAVAILABLE',
                'message' => 'تعذر إرسال رمز التحقق عبر البريد الإلكتروني.',
            ];
        }

        /*
         * إبطال أي OTP سابق لم يتم استخدامه.
         */
        OtpCode::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->where('expires_at', '>', $now)
            ->update([
                'expires_at' => $now,
            ]);

        /*
         * حفظ OTP الجديد.
         */
        OtpCode::create([
            'email' => $email,
            'code_hash' => Hash::make($otp),
            'attempts' => 0,
            'send_count' => $sendCount,
            'send_window_started_at' => $sendWindowStartedAt,
            'blocked_until' => $blockedUntil,
            'expires_at' => $now
                ->copy()
                ->addSeconds(self::OTP_EXPIRES_IN),
            'last_sent_at' => $now,
        ]);

        return [
            'success' => true,
            'expires_in' => self::OTP_EXPIRES_IN,
            'resend_after' => self::RESEND_AFTER,
        ];
    }

    public function verify(
        string $email,
        string $code
    ): array {
        $otp = OtpCode::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return [
                'success' => false,
                'code' => 'OTP_NOT_FOUND',
                'message' => 'لم يتم العثور على رمز تحقق صالح.',
            ];
        }

        if ($otp->isExpired()) {
            return [
                'success' => false,
                'code' => 'OTP_EXPIRED',
                'message' => 'انتهت صلاحية رمز التحقق.',
            ];
        }

        if ($otp->hasExceededAttempts()) {
            return [
                'success' => false,
                'code' => 'OTP_MAX_ATTEMPTS',
                'message' => 'تم تجاوز عدد محاولات التحقق المسموح بها.',
            ];
        }

        /*
         * الكود غير صحيح.
         */
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            $remaining = max(
                0,
                self::MAX_ATTEMPTS - $otp->attempts
            );

            if ($remaining === 0) {
                $otp->update([
                    'expires_at' => now(),
                ]);

                return [
                    'success' => false,
                    'code' => 'OTP_MAX_ATTEMPTS',
                    'message' => 'تم تجاوز عدد محاولات التحقق المسموح بها.',
                    'attempts_remaining' => 0,
                ];
            }

            return [
                'success' => false,
                'code' => 'OTP_INVALID',
                'message' => 'رمز التحقق غير صحيح.',
                'attempts_remaining' => $remaining,
            ];
        }

        /*
         * OTP صحيح.
         */
        $otp->update([
            'verified_at' => now(),
        ]);

        return [
            'success' => true,
            'email' => $email,
        ];
    }
}