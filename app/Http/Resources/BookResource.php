<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'cover_image' => $this->cover_image ? url('storage/' . $this->cover_image) : null,
            'pdf_file' => $this->pdf_file ? url('storage/' . $this->pdf_file) : null,
            'audio_file' => $this->audio_file ? url('storage/' . $this->audio_file) : null,
            'average_rating' => (float) $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'total_sales' => $this->total_sales,
            'is_active' => $this->is_active,
            'is_favorited' => $user ? $this->isFavoritedBy($user->id) : false,
            'category' => [
                'id' => $this->category->id,
                'name_ar' => $this->category->name_ar,
                'name_en' => $this->category->name_en,
            ],
            'library' => [
                'id' => $this->libraryOwner->id,
                'name' => $this->libraryOwner->library_name,
                'address' => $this->libraryOwner->library_address,
                'latitude' => $this->libraryOwner->library_latitude,
                'longitude' => $this->libraryOwner->library_longitude,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
