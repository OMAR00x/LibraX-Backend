<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class OtpService
{
    private const MAX_ATTEMPTS = 5;
    private const OTP_EXPIRY_MINUTES = 15;
    private const ATTEMPT_EXPIRY_HOURS = 24;

    public function getAttemptCount(string $identifier, string $type = 'registration'): array
    {
        $key = $this->getAttemptKey($identifier, $type);
        $attempts = (int) Cache::get($key, 0);

        return [
            'current' => $attempts,
            'max' => self::MAX_ATTEMPTS,
            'remaining' => max(0, self::MAX_ATTEMPTS - $attempts)
        ];
    }

    public function incrementAttempts(string $identifier, string $type = 'registration'): int
    {
        $key = $this->getAttemptKey($identifier, $type);
        $attempts = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addHours(self::ATTEMPT_EXPIRY_HOURS));
        return $attempts;
    }

    public function hasExceededAttempts(string $identifier, string $type = 'registration'): bool
    {
        return $this->getAttemptCount($identifier, $type)['current'] >= self::MAX_ATTEMPTS;
    }

    private function getAttemptKey(string $identifier, string $type): string
    {
        $prefix = match($type) {
            'registration' => 'otp_attempts',
            'password_reset' => 'password_reset_attempts',
            'phone_change' => 'phone_change_attempts',
            default => 'otp_attempts'
        };
        return "{$prefix}:{$identifier}";
    }

    public function generateOtp(): string
    {
        return (string) random_int(10000, 99999);
    }

    public function storeOtp(string $otp, string $phone): void
    {
        $otp = trim($otp);
        Cache::put('otp_code_' . $otp, $phone, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
    }

    public function verifyOtp(string $otp): ?string
    {
        $otp = trim($otp);
        return Cache::get('otp_code_' . $otp);
    }

    public function deleteOtp(string $otp): void
    {
        $otp = trim($otp);
        Cache::forget('otp_code_' . $otp);
    }

    public function storePendingUser(string $phone, array $userData): void
    {
        Cache::put('pending_user_' . $phone, $userData, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
    }

    public function getPendingUser(string $phone): ?array
    {
        return Cache::get('pending_user_' . $phone);
    }

    public function deletePendingUser(string $phone): void
    {
        Cache::forget('pending_user_' . $phone);
    }

    public function storePhoneChangeOtp(string $otp, int $userId, string $newPhone): void
    {
        Cache::put('phone_change_otp_' . $otp, [
            'user_id' => $userId,
            'new_phone' => $newPhone
        ], now()->addMinutes(self::OTP_EXPIRY_MINUTES));
    }

    public function getPhoneChangeData(string $otp): ?array
    {
        return Cache::get('phone_change_otp_' . $otp);
    }

    public function deletePhoneChangeOtp(string $otp): void
    {
        Cache::forget('phone_change_otp_' . $otp);
    }

    public function storePhoneChangeVerification(int $userId, string $newPhone): void
    {
        Cache::put('phone_change_verified_' . $userId, $newPhone, now()->addMinutes(10));
    }

    public function getVerifiedPhoneChange(int $userId): ?string
    {
        return Cache::get('phone_change_verified_' . $userId);
    }

    public function deletePhoneChangeVerification(int $userId): void
    {
        Cache::forget('phone_change_verified_' . $userId);
    }
}
