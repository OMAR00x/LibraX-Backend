<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestOwnerSeeder extends Seeder
{
    /**
     * Create the default test library owner account.
     * Phone: 0984936480 / Password: password
     */
    public function run(): void
    {
        $phone = '0984936480';

        $existing = User::where('phone', $phone)->first();
        if ($existing) {
            $this->command->info("✅ Test owner already exists: {$existing->library_name} (ID: {$existing->id})");
            return;
        }

        $user = User::create([
            'first_name'         => 'صاحب',
            'last_name'          => 'المكتبة الافتراضي',
            'phone'              => $phone,
            'password'           => bcrypt('password'),
            'role'               => 'library_owner',
            'library_name'       => 'مكتبة الاختبار الافتراضية',
            'library_address'    => 'دمشق - وسط المدينة',
            'library_latitude'   => 33.5138,
            'library_longitude'  => 36.2765,
            'wallet_balance'     => 1000,
            'is_active'          => true,
        ]);

        $this->command->info("✅ Test owner created: {$user->library_name} (ID: {$user->id})");
        $this->command->info("   Phone: {$user->phone}");
        $this->command->info("   Role: {$user->role}");
        $this->command->info("   Password: password");
    }
}
