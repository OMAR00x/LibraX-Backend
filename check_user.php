<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::orderBy('id', 'desc')->first();
echo "User Phone: " . $user->phone . "\n";
echo "Password Hash: " . $user->password . "\n";
$isDoubleHashed = \Illuminate\Support\Facades\Hash::needsRehash($user->password);
echo "Needs rehash? " . var_export($isDoubleHashed, true) . "\n";
echo "Try to check with generic password 'password': " . var_export(\Illuminate\Support\Facades\Hash::check('password', $user->password), true) . "\n";
