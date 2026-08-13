<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Throwable;

class OtpService
{
    private const OTP_EXPIRY_SECONDS = 120;
    private const MAX_WRONG_ATTEMPTS = 3;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_SENDS_PER_HOUR = 3;
    private const BLOCK_DURATION_MINUTES = 60;

    public function __construct(
        private WhatsappService $whatsappService
    ) {
    }

    public function send(string $phone): array
    {
         $lastOtp = OtpCode::query()
            ->where('phone', $phone)
            ->latest('id')
            ->first();

        $now = now();

    /*
     * 1. التحقق هل الرقم محظور حاليًا.
     */
        if ($lastOtp?->isBlocked()) {
            return [
                'success' => false,
                'code' => 'OTP_SEND_LIMIT_EXCEEDED',
                'retry_after' => (int) $now->diffInSeconds(
                    $lastOtp->blocked_until
                ),
            ];
        }

    /*
     * 2. منع إعادة الإرسال قبل مرور 60 ثانية.
     */
        if (
            $lastOtp?->last_sent_at !== null
            && $lastOtp->last_sent_at
                ->copy()
                ->addSeconds(self::RESEND_COOLDOWN_SECONDS)
                ->isFuture()
        ) {
            $availableAt = $lastOtp->last_sent_at
                ->copy()
                ->addSeconds(self::RESEND_COOLDOWN_SECONDS);

            return [
                'success' => false,
                'code' => 'OTP_RESEND_TOO_SOON',
            'retry_after' => (int) $now->diffInSeconds($availableAt),
            ];
        }

    /*
     * 3. حساب عدد مرات الإرسال خلال نافذة الساعة.
     *
     * إذا انتهت نافذة الساعة:
     * نبدأ من جديد بـ send_count = 1.
     */
        $sendCount = 1;
        $windowStartedAt = $now;

        if (
            $lastOtp?->send_window_started_at !== null
            && $lastOtp->send_window_started_at
                ->copy()
                ->addHour()
                ->isFuture()
        ) {
            $sendCount = $lastOtp->send_count + 1;
            $windowStartedAt = $lastOtp->send_window_started_at;
        }

    /*
     * حماية إضافية:
     * لا نسمح أبدًا بإرسالية رقم 4 داخل نفس النافذة.
     */
        if ($sendCount > self::MAX_SENDS_PER_HOUR) {
            return [
                'success' => false,
                'code' => 'OTP_SEND_LIMIT_EXCEEDED',
                'retry_after' => self::BLOCK_DURATION_MINUTES * 60,
            ];
        }

    /*
     * 4. إذا كانت هذه الإرسالية الثالثة，
     * سيتم إرسالها بشكل طبيعي，
     * لكن بعدها يمنع طلب OTP جديد لمدة ساعة.
     */
        $blockedUntil = null;

        if ($sendCount === self::MAX_SENDS_PER_HOUR) {
             $blockedUntil = $now->copy()
                ->addMinutes(self::BLOCK_DURATION_MINUTES);
        }

    /*
     * 5. WhatsappService:
     * - يولد OTP
     * - يرسله للمستخدم
     * - يرجع الكود إلى OtpService
     */
        try {
            $plainCode = $this->whatsappService->sendOtp($phone);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'code' => 'WHATSAPP_PROVIDER_UNAVAILABLE',
            ];
        }

    /*
     * 6. بعد نجاح الإرسال نحفظ Hash الكود.
     */
        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,

            'send_count' => $sendCount,
            'send_window_started_at' => $windowStartedAt,
            'blocked_until' => $blockedUntil,

            'expires_at' => $now->copy()
                ->addSeconds(self::OTP_EXPIRY_SECONDS),

            'last_sent_at' => $now,
            'verified_at' => null,
        ]);

    /*
     * 7. إلغاء جميع الأكواد السابقة غير المستخدمة.
     *
     * لا نحذفها حتى نحتفظ بسجل عمليات الإرسال.
     */
        OtpCode::query()
            ->where('phone', $phone)
            ->where('id', '!=', $otp->id)
            ->whereNull('verified_at')
            ->update([
                'expires_at' => $now,
            ]);

    /*
     * 8. نجاح العملية.
     */
        return [
            'success' => true,
            'expires_in' => self::OTP_EXPIRY_SECONDS,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }



    public function verify(string $phone, string $code): array
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return [
                'success' => false,
                'code' => 'OTP_NOT_FOUND',
            ];
        }

        if ($otp->isExpired()) {
            return [
                'success' => false,
                'code' => 'OTP_EXPIRED',
            ];
        }

        if ($otp->hasExceededAttempts()) {
            return [
                'success' => false,
                'code' => 'OTP_MAX_ATTEMPTS',
            ];
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            $otp->refresh();

            $remaining = max(
                0,
                self::MAX_WRONG_ATTEMPTS - $otp->attempts
            );

            if ($remaining === 0) {
                $otp->update([
                    'expires_at' => now(),
                ]);

                return [
                    'success' => false,
                    'code' => 'OTP_MAX_ATTEMPTS',
                    'attempts_remaining' => 0,
                ];
            }

            return [
                'success' => false,
                'code' => 'OTP_INVALID',
                'attempts_remaining' => $remaining,
            ];
        }

        $otp->update([
            'verified_at' => now(),
        ]);

        return [
            'success' => true,
            'phone' => $phone,
        ];
    }
}