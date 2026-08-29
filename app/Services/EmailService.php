<?php

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendOtp(
        string $email,
        string $otp
    ): void {
        Mail::to($email)->send(
            new OtpMail($otp)
        );
    }
}