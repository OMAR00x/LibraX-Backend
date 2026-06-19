<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = auth('sanctum')->user() ?? $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'cover_image' => $this->cover_image ? url('storage/' . $this->cover_image) : null,
            'pdf_file' => ($this->pdf_file && (($this->pdf_access ?? 'purchase') === 'free' || ($user && ($user->isAdmin() || $user->id === $this->library_owner_id || \App\Models\Order::where('customer_id', $user->id)->where('book_id', $this->id)->whereIn('status', ['accepted', 'completed'])->exists())))) ? url('storage/' . $this->pdf_file) : null,
            'audio_file' => ($this->audio_file && (($this->audio_access ?? 'purchase') === 'free' || ($user && ($user->isAdmin() || $user->id === $this->library_owner_id || \App\Models\Order::where('customer_id', $user->id)->where('book_id', $this->id)->whereIn('status', ['accepted', 'completed'])->exists())))) ? url('storage/' . $this->audio_file) : null,
            'pdf_access' => $this->pdf_access ?? 'purchase',
            'audio_access' => $this->audio_access ?? 'purchase',
            'average_rating' => (float) $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'total_sales' => $this->total_sales,
            'is_active' => $this->is_active,
            'page_count' => $this->page_count,
            'parts_count' => $this->parts_count,
            'is_favorited' => $user ? $this->isFavoritedBy($user->id) : false,
            'category' => [
                'id' => $this->category->id,
                'name_ar' => $this->category->name_ar,
                'name_en' => $this->category->name_en,
                'icon' => $this->category->icon,
            ],
            'library' => $this->libraryOwner ? [
                'id' => $this->libraryOwner->id,
                'name' => $this->libraryOwner->library_name,
                'address' => $this->libraryOwner->library_address,
                'phone' => $this->libraryOwner->phone,
                'latitude' => $this->libraryOwner->library_latitude,
                'longitude' => $this->libraryOwner->library_longitude,
                'description' => $this->libraryOwner->library_description,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
