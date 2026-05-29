<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeCharge($query)
    {
        return $query->where('type', 'charge');
    }

    public function scopePurchase($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopeRefund($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeEarning($query)
    {
        return $query->where('type', 'earning');
    }
}
