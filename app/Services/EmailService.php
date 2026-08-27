<?php

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendOtp(string $email): string
    {
        $otp = (string) random_int(100000, 999999);

        Mail::to($email)->send(
            new OtpMail($otp)
        );

        return $otp;
    }
}