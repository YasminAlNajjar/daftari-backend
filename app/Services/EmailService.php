<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmailService
{
    public function sendOtp(
        string $email,
        string $otp
    ): void {
        $response = Http::withHeaders([
            'api-key' => config('services.brevo.api_key'),
            'accept' => 'application/json',
        ])
            ->timeout(15)
            ->post(
                'https://api.brevo.com/v3/smtp/email',
                [
                    'sender' => [
                        'name' => config(
                            'services.brevo.sender_name'
                        ),
                        'email' => config(
                            'services.brevo.sender_email'
                        ),
                    ],

                    'to' => [
                        [
                            'email' => $email,
                        ],
                    ],

                    'subject' => 'رمز التحقق - دفتري',

                    'htmlContent' => "
                        <html>
                            <body>
                                <h2>رمز التحقق الخاص بك</h2>

                                <p>
                                    استخدم الرمز التالي لإكمال تسجيل الدخول:
                                </p>

                                <h1>{$otp}</h1>

                                <p>
                                    الرمز صالح لمدة دقيقتين.
                                </p>
                            </body>
                        </html>
                    ",
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Brevo email sending failed: '
                . $response->body()
            );
        }
    }
}