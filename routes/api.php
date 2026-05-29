<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WalletController;


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

    // Home
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/home/search', [HomeController::class, 'search']);
    Route::get('/home/filter', [HomeController::class, 'filter']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Books
    Route::get('/books/category/{categoryId}', [BookController::class, 'getByCategory']);
    Route::get('/books/{id}', [BookController::class, 'show']);

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
    Route::delete('/favorites/{bookId}', [FavoriteController::class, 'destroy']);

    // Orders (Purchase History)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{id}/rate', [OrderController::class, 'rate']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/charge-request', [WalletController::class, 'requestCharge']);
    Route::get('/wallet/charge-requests', [WalletController::class, 'chargeRequests']);
    Route::get('/wallet/charge-requests/{id}', [WalletController::class, 'showChargeRequest']);

    /*
    |--------------------------------------------------------------------------
    | Library Owner Routes (أصحاب المكتبات)
    |--------------------------------------------------------------------------
    */

    // Library Books Management
    Route::prefix('library')->middleware('check.account')->group(function () {
        Route::get('/books', [\App\Http\Controllers\LibraryOwner\LibraryBookController::class, 'index']);
        Route::post('/books', [\App\Http\Controllers\LibraryOwner\LibraryBookController::class, 'store']);
        Route::get('/books/{id}', [\App\Http\Controllers\LibraryOwner\LibraryBookController::class, 'show']);
        Route::post('/books/{id}', [\App\Http\Controllers\LibraryOwner\LibraryBookController::class, 'update']); // POST for file upload
        Route::delete('/books/{id}', [\App\Http\Controllers\LibraryOwner\LibraryBookController::class, 'destroy']);

        // Library Orders Management
        Route::get('/orders', [\App\Http\Controllers\LibraryOwner\LibraryOrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\LibraryOwner\LibraryOrderController::class, 'show']);
        Route::post('/orders/{id}/accept', [\App\Http\Controllers\LibraryOwner\LibraryOrderController::class, 'accept']);
        Route::post('/orders/{id}/reject', [\App\Http\Controllers\LibraryOwner\LibraryOrderController::class, 'reject']);
        Route::post('/orders/{id}/complete', [\App\Http\Controllers\LibraryOwner\LibraryOrderController::class, 'complete']);

        // Library Statistics
        Route::get('/statistics', [\App\Http\Controllers\LibraryOwner\LibraryStatisticsController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (الأدمن - للويب)
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->middleware('check.account')->group(function () {
        // Users Management
        Route::get('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store']);
        Route::get('/users/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'show']);
        Route::put('/users/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy']);

        // Books Management
        Route::get('/books', [\App\Http\Controllers\Admin\AdminBookController::class, 'index']);
        Route::get('/books/{id}', [\App\Http\Controllers\Admin\AdminBookController::class, 'show']);
        Route::put('/books/{id}', [\App\Http\Controllers\Admin\AdminBookController::class, 'update']);
        Route::delete('/books/{id}', [\App\Http\Controllers\Admin\AdminBookController::class, 'destroy']);

        // Charge Requests Management
        Route::get('/charge-requests', [\App\Http\Controllers\Admin\AdminChargeRequestController::class, 'index']);
        Route::get('/charge-requests/{id}', [\App\Http\Controllers\Admin\AdminChargeRequestController::class, 'show']);
        Route::post('/charge-requests/{id}/approve', [\App\Http\Controllers\Admin\AdminChargeRequestController::class, 'approve']);
        Route::post('/charge-requests/{id}/reject', [\App\Http\Controllers\Admin\AdminChargeRequestController::class, 'reject']);

        // System Statistics
        Route::get('/statistics', [\App\Http\Controllers\Admin\AdminStatisticsController::class, 'index']);
    });

});
