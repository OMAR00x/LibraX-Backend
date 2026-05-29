<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
{
    /**
     * Resource للقائمة - بيانات مختصرة
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'price' => (float) $this->price,
            'cover_image' => $this->cover_image ? url('storage/' . $this->cover_image) : null,
            'average_rating' => (float) $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'is_favorited' => $user ? $this->isFavoritedBy($user->id) : false,
            'category_name_ar' => $this->category->name_ar,
            'category_name_en' => $this->category->name_en,
            'library_name' => $this->libraryOwner->library_name,
        ];
    }
}
