<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserAuthService
{
    public function phoneExists(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    public function createUser(array $userData): User
    {
        $user = User::create([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'phone' => $userData['phone'],
            'password' => $userData['password'],
            'role' => 'customer',
            'is_active' => true,
        ]);

        return $user;
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function createToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }



    public function updatePassword(User $user, string $newPassword): void
    {
        $user->password = Hash::make($newPassword);
        $user->save();
    }

    public function updatePhone(User $user, string $newPhone): void
    {
        $user->phone = $newPhone;
        $user->save();
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? null,
        ], fn($value) => !is_null($value)));

        $user->save();
        return $user->fresh();
    }
}
