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
        'locale',
        'library_name',
        'library_address',
        'library_latitude',
        'library_longitude',
        'library_description',
        'wallet_balance',
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
            'wallet_balance' => 'decimal:2',
            'library_latitude' => 'decimal:8',
            'library_longitude' => 'decimal:8',
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

    public function books()
    {
        return $this->hasMany(Book::class, 'library_owner_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function libraryOrders()
    {
        return $this->hasMany(Order::class, 'library_owner_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function chargeRequests()
    {
        return $this->hasMany(ChargeRequest::class);
    }

    public function reviews()
    {
        return $this->hasMany(BookReview::class);
    }

    public function highlights()
    {
        return $this->hasMany(BookHighlight::class);
    }

    public function notes()
    {
        return $this->hasMany(BookNote::class);
    }

    public function quotes()
    {
        return $this->hasMany(BookQuote::class);
    }

    public function readingProgresses()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    public function isLibraryOwner()
    {
        return $this->role === 'library_owner';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

}
