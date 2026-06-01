<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $table = 'reading_progresses';

    protected $fillable = [
        'user_id',
        'book_id',
        'last_page',
        'progress_percent',
        'total_reading_seconds',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'progress_percent' => 'decimal:2',
        'total_reading_seconds' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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
