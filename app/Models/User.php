<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'password',
        'is_active',
        'role',
    ];

    // ✅ لا حاجة لـ $guarded عند استخدام $fillable
    // Laravel تلقائياً يحمي الحقول غير الموجودة في $fillable

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if phone is unique among non-deleted users
     */
    public static function isPhoneUnique($phone, $excludeId = null)
    {
        $query = static::where('phone', $phone)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }



    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

}
