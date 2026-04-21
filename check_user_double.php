<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$plain = '12345678';
$hashed = \Illuminate\Support\Facades\Hash::make($plain);
echo "Manually hashed: $hashed\n";

$user = new \App\Models\User();
$user->password = $hashed;

echo "User password after assignment: " . $user->password . "\n";
if ($user->password === $hashed) {
    echo "It was NOT double hashed.\n";
} else {
    echo "It WAS double hashed!\n";
}
