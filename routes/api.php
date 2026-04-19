<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;


Route::get('/', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'LibraX API is running',
        'timestamp' => now()->toISOString(),
    ]);
});





/*
|--------------------------------------------------------------------------
| Public Routes (الزوار - بدون تسجيل دخول)
|--------------------------------------------------------------------------
*/


// Auth
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/login/qr', [AuthController::class, 'loginWithQR'])->middleware('throttle:login');
Route::post('/preRegister', [AuthController::class, 'preRegister'])->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'verifyOtpAndRegister'])->middleware('throttle:login');
Route::post('/resend-register-otp', [AuthController::class, 'resendRegistrationOtp'])->middleware('throttle:login');


// Password Reset
Route::post('/password/send-otp', [PasswordResetController::class, 'sendUserPasswordResetOtp']);
Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyOtp']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::post('/password/resend-otp', [PasswordResetController::class, 'resendPasswordResetOtp']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (مشتركة لجميع المستخدمين المسجلين)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {


    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/check-status', [AuthController::class, 'checkStatus']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->middleware('check.account');



    // Password & Phone
    Route::post('/password/change', [PasswordResetController::class, 'changePassword'])->middleware('check.account');
    Route::post('/phone/request-change', [PasswordResetController::class, 'requestPhoneChange'])->middleware('check.account');
    Route::post('/phone/verify-change', [PasswordResetController::class, 'verifyAndApplyPhoneChange'])->middleware('check.account');

    // FCM Token - بدون check.account
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);

    // Notifications - بدون check.account
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/send-test', [NotificationController::class, 'sendTest']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('/notifications', [NotificationController::class, 'deleteAll']);

    /*
    |--------------------------------------------------------------------------
    | Customer Routes (العملاء)
    |--------------------------------------------------------------------------
    */

});
