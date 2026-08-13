<?php

// namespace App\Services;

// use Illuminate\Http\Client\Response;
// use Illuminate\Support\Facades\Http;
// use RuntimeException;

// class WhatsappService
// {
//     private string $token;
//     private string $phoneNumberId;
//     private string $apiVersion;

//     public function __construct()
//     {
//         $this->token = (string) config('services.whatsapp.token');
//         $this->phoneNumberId = (string) config(
//             'services.whatsapp.phone_number_id'
//         );
//         $this->apiVersion = (string) config(
//             'services.whatsapp.api_version',
//             'v25.0'
//         );
//     }

//     public function sendOtp(string $phone, string $otp): array
//     {
//         /*
//          * حاليًا نستخدم قالب Meta التجريبي الجاهز hello_world.
//          * لاحقًا سننشئ قالب Authentication مخصصًا للـ OTP.
//          */
//         $response = $this->sendTemplate(
//             phone: $phone,
//             templateName: 'hello_world',
//             languageCode: 'en_US'
//         );

//         return $response->json();
//     }

//     public function sendTemplate(
//         string $phone,
//         string $templateName,
//         string $languageCode = 'en_US',
//         array $components = []
//     ): Response {
//         $this->ensureConfigurationExists();

//         $payload = [
//             'messaging_product' => 'whatsapp',
//             'to' => $this->normalizePhone($phone),
//             'type' => 'template',
//             'template' => [
//                 'name' => $templateName,
//                 'language' => [
//                     'code' => $languageCode,
//                 ],
//             ],
//         ];

//         if ($components !== []) {
//             $payload['template']['components'] = $components;
//         }

//         return Http::withToken($this->token)
//             ->acceptJson()
//             ->post($this->messagesUrl(), $payload)
//             ->throw();
//     }

//     private function messagesUrl(): string
//     {
//         return sprintf(
//             'https://graph.facebook.com/%s/%s/messages',
//             $this->apiVersion,
//             $this->phoneNumberId
//         );
//     }

//     private function normalizePhone(string $phone): string
//     {
//         return preg_replace('/\D+/', '', $phone) ?? $phone;
//     }

//     private function ensureConfigurationExists(): void
//     {
//         if (
//             $this->token === ''
//             || $this->phoneNumberId === ''
//             || $this->apiVersion === ''
//         ) {
//             throw new RuntimeException(
//                 'WhatsApp API configuration is missing.'
//             );
//         }
//     }
// }



namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappService
{
    private string $token;
    private string $phoneNumberId;
    private string $apiVersion;

    public function __construct()
    {
        $this->token = (string) config('services.whatsapp.token');
        $this->phoneNumberId = (string) config(
            'services.whatsapp.phone_number_id'
        );
        $this->apiVersion = (string) config(
            'services.whatsapp.api_version',
            'v25.0'
        );
    }

    public function sendOtp(string $phone): string
    {
        $otp = (string) random_int(100000, 999999);

        $this->sendText(
            phone: $phone,
            message: "كودك للتحقق هو: {$otp}"
         );

        return $otp;
    }

    public function sendText(string $phone, string $message): Response
    {
        $this->ensureConfigurationExists();

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($phone),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        return Http::withToken($this->token)
            ->acceptJson()
            ->post($this->messagesUrl(), $payload)
            ->throw();
    }

    public function sendTemplate(
        string $phone,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = []
    ): Response {
        $this->ensureConfigurationExists();

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($phone),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return Http::withToken($this->token)
            ->acceptJson()
            ->post($this->messagesUrl(), $payload)
            ->throw();
    }

    private function messagesUrl(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->apiVersion,
            $this->phoneNumberId
        );
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    private function ensureConfigurationExists(): void
    {
        if (
            $this->token === ''
            || $this->phoneNumberId === ''
            || $this->apiVersion === ''
        ) {
            throw new RuntimeException(
                'WhatsApp API configuration is missing.'
            );
        }
    }
}

// namespace App\Services;

// use RuntimeException;
// use Illuminate\Support\Facades\Http;

// class WhatsappService
// {
//     private string $baseUrl;
//     private string $apiKey;

//     public function __construct()
//     {
//         // قراءة البيانات من إعدادات Laravel التي ترتبط بالـ .env
//         $this->baseUrl = (string) config('services.infobip.base_url');
//         $this->apiKey = (string) config('services.infobip.api_key');

//         $this->ensureConfigurationExists();
//     }

//     /**
//      * إرسال كود التفعيل (OTP) مباشرة للهاتف الفلسطيني
//      */
//     public function sendOtp(string $phone, string $otp): array
//     {
//         $messageText = "كودك للتحقق هو: {$otp}";
        
//         return $this->sendText($phone, $messageText);
//     }

//     /**
//      * إرسال رسالة نصية حرة باستخدام Http Client الخاص بـ Laravel
//      */
//     public function sendText(string $phone, string $messageText): array
//     {
//         $normalizedPhone = $this->normalizePhone($phone);

//         // بناء الرابط المباشر لإرسال الـ SMS حسب توثيق Infobip الرسمي
//         $url = rtrim($this->baseUrl, '/') . '/sms/3/messages';

//         // تجهيز الـ Payload المتوافق مع الـ API
//         $payload = [
//             'messages' => [
//                 [
//                     'from' => 'InfoSMS', // اسم المرسل الافتراضي للحسابات التجريبية
//                     'destinations' => [
//                         ['to' => $normalizedPhone]
//                     ],
//                     'text' => $messageText
//                 ]
//             ]
//         ];

//         try {
//             // إرسال الطلب مع ترويسة التحقق (Authorization Header)
//             $response = Http::withHeaders([
//                 'Authorization' => 'App ' . $this->apiKey,
//                 'Content-Type' => 'application/json',
//                 'Accept' => 'application/json',
//             ])->post($url, $payload);

//             if ($response->successful()) {
//                 return [
//                     'success' => true,
//                     'data' => $response->json()
//                 ];
//             }

//             return [
//                 'success' => false,
//                 'error' => 'Infobip API Error: ' . $response->body()
//             ];

//         } catch (\Exception $e) {
//             return [
//                 'success' => false,
//                 'error' => 'Connection Error: ' . $e->getMessage()
//             ];
//         }
//     }

//     /**
//      * معالجة الرقم الفلسطيني تلقائياً ليتوافق مع الصيغة الدولية
//      */
//     private function normalizePhone(string $phone): string
//     {
//         // حذف أي رموز غير رقمية مثل + أو مسافات
//         $cleaned = preg_replace('/\D+/', '', $phone) ?? $phone;

//         // تحويل أرقام فلسطين المحلية (تبدأ بـ 05) إلى الصيغة الدولية 970
//         if (str_starts_with($cleaned, '05')) {
//             $cleaned = '970' . substr($cleaned, 1);
//         }

//         return $cleaned;
//     }

//     /**
//      * التحقق من وجود الإعدادات
//      */
//     private function ensureConfigurationExists(): void
//     {
//         if ($this->baseUrl === '' || $this->apiKey === '') {
//             throw new RuntimeException(
//                 'Infobip API configuration is missing in config/services.php or .env'
//             );
//         }
//     }
// }