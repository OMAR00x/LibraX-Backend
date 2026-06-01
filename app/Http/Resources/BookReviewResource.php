<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'rating' => (int) $this->rating,
            'review_content' => $this->review_content,
            'user_name' => $this->user ? ($this->user->first_name . ' ' . $this->user->last_name) : 'مستخدم مكتبة',
            'user_avatar' => $this->user && $this->user->avatar ? url('storage/' . $this->user->avatar) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
