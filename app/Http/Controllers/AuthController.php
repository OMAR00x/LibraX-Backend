<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use App\Services\UserAuthService;
use App\Http\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\PreRegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
  use ResponseTrait;

    public function __construct(
        protected OtpService $otpService,
        protected UserAuthService $authService,
        protected WhatsappService $whatsAppService
    ) {}

     public function preRegister(PreRegisterRequest $request)
    {


        try {
            $validated = $request->validated();
            $phone = $validated['phone'];

            if (User::where('phone', $phone)->exists()) {
                return $this->errorResponse('يوجد حساب مسجل بهذا الرقم بالفعل', ['phone' => ['الرقم مستخدم بالفعل']], 422);
            }

            if ($this->otpService->hasExceededAttempts($phone)) {
                $attemptInfo = $this->otpService->getAttemptCount($phone);
                return $this->errorResponse('تم تجاوز الحد اليومي لرموز التسجيل', $attemptInfo, 429);
            }

            $this->otpService->deletePendingUser($phone);
            $newAttempts = $this->otpService->incrementAttempts($phone);

            $otp = $this->otpService->generateOtp();
            $this->otpService->storeOtp($otp, $phone);

            $validated['password'] = Hash::make($validated['password']);
            $this->otpService->storePendingUser($phone, $validated);

            $whatsAppResult = $this->whatsAppService->sendOtp($phone, $otp);
            if (!$whatsAppResult['success']) {
                $this->otpService->decrementAttempts($phone);
                return $this->errorResponse($whatsAppResult['error'] ?? 'فشل في إرسال كود التحقق', [], 400);
            }

            return $this->successResponse([
                'otp' => app()->isLocal() ? $otp : null,
                'attempts' => $newAttempts,
                'max_attempts' => 5,
                'remaining' => max(0, 5 - $newAttempts)
            ], 'تم إرسال كود التحقق بنجاح');
        } catch (\Throwable $e) {
            if (isset($phone)) {
                $this->otpService->decrementAttempts($phone);
            }
            \Log::error('Registration error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return $this->errorResponse('خطأ داخلي في الخادم', [], 500);
        }

        //return $this->successResponse([], 'نظام التسجيل معطل مؤقتاً للصيانة');
    }

    public function resendRegistrationOtp(ResendOtpRequest $request)
    {
    try {
        $phone = $request->validated()['phone'];

        if ($this->otpService->hasExceededAttempts($phone)) {
            $attemptInfo = $this->otpService->getAttemptCount($phone);
            return $this->errorResponse('تم تجاوز الحد اليومي', $attemptInfo, 429);
        }

        $pendingData = $this->otpService->getPendingUser($phone);
        if (!$pendingData) {
            return $this->errorResponse('لا توجد عملية تسجيل جارية', [], 404);
        }

        $newAttempts = $this->otpService->incrementAttempts($phone);
        $otp = $this->otpService->generateOtp();
        $this->otpService->storeOtp($otp, $phone);

        $whatsAppResult = $this->whatsAppService->sendOtp($phone, $otp);
        if (!$whatsAppResult['success']) {
            $this->otpService->decrementAttempts($phone);
            return $this->errorResponse($whatsAppResult['error'] ?? 'فشل في إرسال كود التحقق', [], 400);
        }

        return $this->successResponse([
            'otp' => app()->isLocal() ? $otp : null,
            'attempts' => $newAttempts,
            'max_attempts' => 5,
            'remaining' => max(0, 5 - $newAttempts)
        ], 'تم إعادة إرسال كود التحقق بنجاح');
    } catch (\Exception $e) {
        if (isset($phone)) {
            $this->otpService->decrementAttempts($phone);
        }
        \Log::error('OTP resend error', ['error' => $e->getMessage()]);
        return $this->errorResponse('خطأ في إعادة الإرسال', [], 500);
    }
}

  public function verifyOtpAndRegister(VerifyOtpRequest $request)
    {
        try {
            $validated = $request->validated();
            $inputOtp = trim($validated['otp']);
            $phone = $validated['phone'];

            \Log::info('Verifying OTP', [
                'input_otp' => $inputOtp,
                'phone' => $phone,
                'otp_length' => strlen($inputOtp)
            ]);

            $storedPhone = $this->otpService->verifyOtp($inputOtp);

            \Log::info('OTP verification result', [
                'stored_phone' => $storedPhone,
                'input_phone' => $phone,
                'match' => $storedPhone === $phone
            ]);

            if (!$storedPhone || $storedPhone !== $phone) {
                return $this->errorResponse('كود التحقق غير صحيح أو منتهي الصلاحية', [], 410);
            }

            if (User::where('phone', $phone)->exists()) {
                return $this->errorResponse('يوجد حساب مسجل بهذا الرقم بالفعل', [], 422);
            }

            $userData = $this->otpService->getPendingUser($phone);
            if (!$userData) {
                return $this->errorResponse('انتهت صلاحية بيانات التسجيل، يرجى إعادة التسجيل', [], 410);
            }

            $newUser = $this->authService->createUser($userData);
            $token = $this->authService->createToken($newUser);

            $this->otpService->deleteOtp($inputOtp);
            $this->otpService->deletePendingUser($phone);


            return $this->successResponse(['user' => new UserResource($newUser), 'token' => $token], 'تم التحقق وإنشاء الحساب بنجاح');
        } catch (\Exception $e) {
            \Log::error('OTP verification error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return $this->errorResponse('خطأ داخلي في الخادم', [], 500);
        }
    }

public function login(LoginRequest $request)
    {
    try {
        $validated = $request->validated();
        $user = $this->authService->findByPhone($validated['phone']);

        if (!$user) {
            \Log::error('Login failed: User not found', ['phone' => $validated['phone']]);
            return $this->errorResponse('الرقم أو كلمة المرور غير صحيحة', [], 401);
        }
        
        if (!$this->authService->verifyPassword($user, $validated['password'])) {
            \Log::error('Login failed: Wrong password', ['phone' => $validated['phone']]);
            return $this->errorResponse('الرقم أو كلمة المرور غير صحيحة', [], 401);
        }

        if (!$user->is_active) {
            \Log::error('Login failed: User inactive', ['phone' => $validated['phone']]);
            return $this->errorResponse('حسابك غير فعال، يرجى التواصل مع الإدارة', [], 403);
        }

        $token = $this->authService->createToken($user);
        \Log::info('Login successful', ['phone' => $validated['phone']]);

        return $this->successResponse(['user' => new UserResource($user), 'token' => $token], 'تم تسجيل الدخول بنجاح');
    } catch (\Exception $e) {
        \Log::error('Login error', ['error' => $e->getMessage()]);
        return $this->errorResponse('خطأ داخلي في الخادم لدينا', [], 500);
    }
}


    public function logout()
    {
           try {
            /** @var \App\Models\User $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return $this->errorResponse('لم يتم العثور على جلسة تسجيل دخول صالحة', [], 401);
            }

            // حذف كل FCM tokens للمستخدم عشان ما توصله إشعارات أبداً
            $user->fcmTokens()->delete();

            // حذف الـ access token الحالي
            $user->currentAccessToken()?->delete();

            return $this->successResponse([], 'تم تسجيل الخروج بنجاح');
        } catch (\Exception $e) {
            \Log::error('Logout error', ['error' => $e->getMessage()]);
            return $this->errorResponse('خطأ في تسجيل الخروج', [], 500);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'تم تحديث الملف الشخصي', 'user' => $user->fresh()]);
    }



    public function checkStatus()
    {
        try {
            $user = auth('sanctum')->user();

            if (!$user) {
                return $this->errorResponse('يجب تسجيل الدخول أولاً', [], 401);
            }

            return $this->successResponse([
                'is_active' => $user->is_active,
                'user_id' => $user->id,
            ], 'تم فحص حالة الحساب');
        } catch (\Exception $e) {
            \Log::error('Check status error', ['error' => $e->getMessage()]);
            return $this->errorResponse('خطأ في فحص الحالة', [], 500);
        }
    }


}
