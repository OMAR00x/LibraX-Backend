<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'library_owner_id',
        'category_id',
        'title',
        'author',
        'description',
        'price',
        'quantity',
        'page_count',
        'parts_count',
        'cover_image',
        'pdf_file',
        'audio_file',
        'pdf_access',
        'audio_access',
        'average_rating',
        'total_ratings',
        'total_sales',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'page_count' => 'integer',
        'parts_count' => 'integer',
    ];

    public function libraryOwner()
    {
        return $this->belongsTo(User::class, 'library_owner_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isFavoritedBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeFilterByPrice($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeFilterByRating($query, $minRating = null)
    {
        if ($minRating !== null) {
            $query->where('average_rating', '>=', $minRating);
        }
        return $query;
    }

    public function scopeSortBy($query, $sortBy = 'created_at', $sortOrder = 'desc')
    {
        return $query->orderBy($sortBy, $sortOrder);
    }
}
