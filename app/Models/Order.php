<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'book_id',
        'library_owner_id',
        'price',
        'quantity',
        'payment_method',
        'status',
        'rejection_reason',
        'cancellation_reason',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function libraryOwner()
    {
        return $this->belongsTo(User::class, 'library_owner_id');
    }

    public function walletTransaction()
    {
        return $this->hasOne(WalletTransaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function canBeCancelled()
    {
        return $this->status === 'pending';
    }

    public function canBeRated()
    {
        return $this->status === 'completed' && $this->rating === null;
    }
}
