<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
         web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // معالجة خطأ 413 Payload Too Large
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 413 && $request->expectsJson()) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'حجم البيانات المرسلة كبير جداً. يرجى تقليل حجم الصور (الحد الأقصى 8MB لكل صورة)',
                ], 413);
            }
        });

        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
$exceptions->renderable(function (AuthenticationException $e, $request) {
    return response()->json([
        'status' => 'failure',
        'message' => 'غير مصرح، يرجى تسجيل الدخول',
        'data' => null,
    ], 401);
});

        $exceptions->renderable(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'لقد تجاوزت الحد المسموح به من المحاولات. يرجى المحاولة لاحقاً.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                Log::error($e->__toString());

                return response()->json([
                    'status' => 'failure',
                    'message' => 'الصفحة التي تريدها غير موجودة-',
                    'data' => null,
                ], 404);
            }
        });
    })->create();
