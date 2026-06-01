<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send OTP via SMS (Simulated for development)
     */
    public function sendOtp($phone, $otp)
    {
        Log::info("📨 [SMS Simulated] Sending OTP {$otp} to phone {$phone}");
        
        return ['success' => true];
    }
}
