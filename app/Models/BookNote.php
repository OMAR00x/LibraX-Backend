<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookNote extends Model
{
    use HasFactory;

    protected $table = 'book_notes';

    protected $fillable = [
        'user_id',
        'book_id',
        'highlight_text',
        'note_content',
        'page_number',
    ];

    protected $casts = [
        'page_number' => 'integer',
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
