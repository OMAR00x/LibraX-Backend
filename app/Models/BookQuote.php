<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookQuote extends Model
{
    use HasFactory;

    protected $table = 'book_quotes';

    protected $fillable = [
        'user_id',
        'book_id',
        'quote_text',
        'category_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
