<?php

/**
 * اختبار Login بسرعة
 * 
 * استخدم هذا الكود في tinker:
 * php artisan tinker
 * ثم انسخ الكود التالي
 */

// 1️⃣ إنشاء مستخدم تجريبي
$user = \App\Models\User::create([
    'first_name' => 'أحمد',
    'last_name' => 'محمد',
    'phone' => '966500000001',
    'password' => \Hash::make('password123'),
    'role' => 'customer',
    'is_active' => true,
]);

echo "✅ تم إنشاء المستخدم: {$user->first_name} {$user->last_name}\n";
echo "📱 الهاتف: {$user->phone}\n";
echo "🔑 كلمة المرور: password123\n\n";

// 2️⃣ اختبار Login API
echo "🧪 اختبار Login API...\n";

$response = \Http::post('http://127.0.0.1:8000/api/login', [
    'phone' => '966500000001',
    'password' => 'password123',
]);

echo "📊 Status: " . $response->status() . "\n";
echo "📦 Response:\n";
print_r($response->json());

// 3️⃣ اختبار كلمة مرور خاطئة
echo "\n🧪 اختبار كلمة مرور خاطئة...\n";

$response = \Http::post('http://127.0.0.1:8000/api/login', [
    'phone' => '966500000001',
    'password' => 'wrongpassword',
]);

echo "📊 Status: " . $response->status() . "\n";
echo "📦 Response:\n";
print_r($response->json());

// 4️⃣ اختبار حساب غير موجود
echo "\n🧪 اختبار حساب غير موجود...\n";

$response = \Http::post('http://127.0.0.1:8000/api/login', [
    'phone' => '966500000099',
    'password' => 'password123',
]);

echo "📊 Status: " . $response->status() . "\n";
echo "📦 Response:\n";
print_r($response->json());

// 5️⃣ تنظيف - حذف المستخدم التجريبي
echo "\n🧹 تنظيف البيانات...\n";
$user->forceDelete();
echo "✅ تم حذف المستخدم التجريبي\n";
