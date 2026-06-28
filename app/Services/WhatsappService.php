<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    private $apiUrl;
    private $apiKey;
    private $senderPhone;

    public function __construct()
    {
        // الطريقة الصحيحة للقراءة من الكاش
        $this->apiUrl = config('services.sidobe.api_url');
        $this->apiKey = config('services.sidobe.secret_key');
        $this->senderPhone = config('services.sidobe.sender_phone');
    }
    public function sendOtp($phone, $otp)
    {
        $phone = $this->formatPhone($phone);
        $locale = app()->getLocale();

        if ($locale === 'en') {
            $message = "Your verification code is:\n\n{$otp}\n\nDo not share this code with anyone.";
        } else {
            $message = "رمز التحقق الخاص بك هو:\n\n{$otp}\n\nلا تشارك هذا الرمز مع أي شخص.";
        }

        Log::info("Attempting to send WhatsApp OTP via Sidobe", [
            'phone' => $phone,
            'locale' => $locale,
            'api_url' => $this->apiUrl,
            'sender' => $this->senderPhone
        ]);

        try {
            $response = Http::withHeaders([
                'X-Secret-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(10)
              ->retry(3, 100)
              ->post($this->apiUrl . '/send-message', [
                'phone' => $phone,
                'message' => $message,
                'is_async' => true,
                'sender_phone' => $this->senderPhone
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['is_success'] ?? false)) {
                Log::info("WhatsApp OTP sent successfully via Sidobe", [
                    'phone' => $phone,
                    'message_id' => $data['data']['id'] ?? null
                ]);
                $this->logMessage($phone, $message, $otp, 'sent', 'sidobe', $data['data']['id'] ?? null);
                return ['success' => true];
            }

            $error = $data['message'] ?? 'Failed sending message from Sidobe provider';
            Log::error("WhatsApp OTP failed response from Sidobe", [
                'phone' => $phone,
                'status' => $response->status(),
                'error' => $error
            ]);
            $this->logMessage($phone, $message, $otp, 'failed', 'sidobe', null, $error);

        } catch (\Exception $e) {
            Log::error("Exception occurred while sending WhatsApp OTP via Sidobe", [
                'phone' => $phone,
                'exception' => $e->getMessage()
            ]);
            $this->logMessage($phone, $message, $otp, 'failed', 'sidobe', null, $e->getMessage());
        }

        // إذا فشل WhatsApp، جرب SMS
        $smsService = new SmsService();
        return $smsService->sendOtp($phone, $otp);
    }

    private function logMessage($phone, $message, $otp, $status, $provider, $msgId = null, $error = null)
    {
        WhatsappLog::create([
            'phone' => $phone,
            'message' => $message,
            'status' => $status,
            'provider' => $provider,
            'provider_message_id' => $msgId,
            'error_message' => $error,
            'otp' => $otp,
            'sent_at' => now(),
        ]);
    }

    private function formatPhone($phone)
    {
        // تنظيف الرقم من أي رموز
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // التحقق إذا كان يبدأ بـ 0 (لأرقام سوريا مثلاً) وتحويله لصيغة دولية مع +
        if (substr($phone, 0, 1) === '0') {
            $phone = '963' . substr($phone, 1);
        }

        // التوثيق يطلب صيغة E.164 (يجب أن يبدأ بـ +)
        return '+' . $phone;
    }
}
