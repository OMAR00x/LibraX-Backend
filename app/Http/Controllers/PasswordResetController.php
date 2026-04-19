<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use App\Http\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Password\SendPasswordResetOtpRequest;
use App\Http\Requests\Password\ChangePasswordRequest;
use App\Http\Requests\Password\RequestPhoneChangeRequest;
use App\Http\Requests\Password\ResetPasswordRequest;

class PasswordResetController extends Controller
{
    use ResponseTrait;

    private function getAttemptCount(string $phone): array
    {
        $key = "password_reset_attempts:{$phone}";
        $attempts = (int) Cache::get($key, 0);

        return [
            'current' => $attempts,
            'max' => 5,
            'remaining' => max(0, 5 - $attempts)
        ];
    }

    private function incrementAttempts(string $phone): int
    {
        $key = "password_reset_attempts:{$phone}";
        $attempts = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addDay());
        return $attempts;
    }

    public function sendUserPasswordResetOtp(SendPasswordResetOtpRequest $request)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                $phone = $user->phone;
            } else {
                $phone = trim($request->validated()['phone']);
            }

            $attemptInfo = $this->getAttemptCount($phone);
            if ($attemptInfo['current'] >= 5) {
                return $this->errorResponse('تم تجاوز الحد اليومي', [
                    'attempts' => $attemptInfo['current'],
                    'max_attempts' => $attemptInfo['max'],
                    'remaining' => $attemptInfo['remaining']
                ], 429);
            }

            $newAttempts = $this->incrementAttempts($phone);

            $otp = rand(10000, 99999);
            Cache::put('password_reset_otp_' . $otp, ['phone' => $phone], now()->addMinutes(15));

            $whatsAppResult = (new WhatsappService())->sendOtp($phone, $otp);
            if (isset($whatsAppResult['success']) && !$whatsAppResult['success']) {
                return $this->errorResponse($whatsAppResult['error'] ?? 'فشل في إرسال رمز التحقق', [], 400);
            }

            return $this->successResponse([
                'attempts' => $newAttempts,
                'max_attempts' => 5,
                'remaining' => max(0, 5 - $newAttempts)
            ], 'تم إرسال رمز التحقق بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في إرسال رمز التحقق', ['error' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $inputOtp = trim($request->validated()['otp']);
            $resetData = Cache::get('password_reset_otp_' . $inputOtp);

            if (!$resetData) {
                return $this->errorResponse('رمز التحقق غير صحيح أو منتهي الصلاحية', [], 410);
            }

            Cache::put('password_reset_verified_' . $resetData['phone'], ['phone' => $resetData['phone']], now()->addMinutes(10));
            Cache::forget('password_reset_otp_' . $inputOtp);

            return $this->successResponse([], 'تم التحقق من الرمز بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في التحقق من الرمز', ['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $phone = $validated['phone'];
            $resetData = Cache::get('password_reset_verified_' . $phone);

            if (!$resetData) {
                return $this->errorResponse('يجب التحقق من رمز OTP أولاً', [], 403);
            }

            $user = User::where('phone', $phone)->first();
            if (!$user) {
                return $this->errorResponse('المستخدم غير موجود', [], 404);
            }

            $user->password = Hash::make($validated['password']);
            $user->password_changed_at = now();
            $user->save();

            Cache::forget('password_reset_verified_' . $phone);

            return $this->successResponse(['user' => $user], 'تم تغيير كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في تغيير كلمة المرور', ['error' => $e->getMessage()], 500);
        }
    }

    public function resendPasswordResetOtp(Request $request)
    {
        return $this->sendUserPasswordResetOtp($request);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('المستخدم غير مصادق عليه', [], 401);
            }

            if (!Hash::check($validated['old_password'], $user->password)) {
                return $this->errorResponse('كلمة المرور القديمة غير صحيحة', [], 422);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->password_changed_at = now();
            $user->save();

            return $this->successResponse(['user' => $user], 'تم تغيير كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('خطأ داخلي في السيرفر', ['error' => $e->getMessage()], 500);
        }
    }

    public function requestPhoneChange(RequestPhoneChangeRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('المستخدم غير مصادق عليه', [], 401);
            }

            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('كلمة المرور الحالية غير صحيحة', [], 422);
            }

            if ($validated['new_phone'] === $user->phone) {
                return $this->errorResponse('هذا رقمك الحالي', ['new_phone' => ['الرقم الجديد يجب أن يكون مختلفًا عن الرقم الحالي']], 422);
            }

            if (User::where('phone', $validated['new_phone'])->where('id', '!=', $user->id)->exists()) {
                return $this->errorResponse('هذا الرقم مستخدم بالفعل', ['new_phone' => ['هذا الرقم موجود بالفعل ولا يمكن التغيير إليه']], 422);
            }

            $otp = rand(10000, 99999);
            Cache::put('phone_change_otp_' . $otp, [
                'user_id' => $user->id,
                'new_phone' => $validated['new_phone']
            ], now()->addMinutes(15));

            $whatsAppResult = (new WhatsappService())->sendOtp($validated['new_phone'], $otp);
            if (isset($whatsAppResult['success']) && !$whatsAppResult['success']) {
                return $this->errorResponse($whatsAppResult['error'] ?? 'فشل في إرسال كود التحقق', [], 400);
            }

            return $this->successResponse([], 'تم إرسال كود التحقق بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('خطأ داخلي في السيرفر', ['error' => $e->getMessage()], 500);
        }
    }

    public function verifyAndApplyPhoneChange(VerifyOtpRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('المستخدم غير مصادق عليه', [], 401);
            }

            $inputOtp = trim($request->validated()['otp']);
            $otpData = Cache::get('phone_change_otp_' . $inputOtp);

            if (!$otpData || $otpData['user_id'] != $user->id) {
                return $this->errorResponse('كود التحقق غير صحيح أو منتهي الصلاحية', [], 422);
            }

            if (User::where('phone', $otpData['new_phone'])->where('id', '!=', $user->id)->exists()) {
                Cache::forget('phone_change_otp_' . $inputOtp);
                return $this->errorResponse('رقم الهاتف مستخدم من قبل مستخدم آخر', [], 422);
            }

            $user->phone = $otpData['new_phone'];
            $user->save();

            Cache::forget('phone_change_otp_' . $inputOtp);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                ]
            ], 'تم تغيير رقم الهاتف بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('فشل تغيير رقم الهاتف', ['error' => $e->getMessage()], 500);
        }
    }
}
