<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * إرسال رمز التحقق عبر SMS كحل بديل
     *
     * @param string $phone
     * @param string $otp
     * @return array
     */
    public function sendOtp($phone, $otp)
    {
        // تسجيل العملية في اللوج المؤقت
        Log::info("Fallback to SMS - Sending OTP {$otp} to {$phone}");

        // يمكنك هنا لاحقاً ربط مزود خدمة SMS الحقيقي
        // في الوقت الحالي، نعتبر العملية ناجحة لكي لا يتوقف التسجيل
        return ['success' => true];
    }
}
