<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'author' => $this->book->author,
                'cover_image' => $this->book->cover_image ? url('storage/' . $this->book->cover_image) : null,
            ],
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->first_name . ' ' . $this->customer->last_name,
                'phone' => $this->customer->phone,
            ],
            'library' => [
                'id' => $this->libraryOwner->id,
                'name' => $this->libraryOwner->library_name,
                'address' => $this->libraryOwner->library_address,
            ],
            'price' => (float) $this->price,
            'payment_method' => $this->payment_method,
            'payment_method_ar' => $this->payment_method === 'cash' ? 'نقدي' : 'محفظة',
            'status' => $this->status,
            'status_ar' => $this->getStatusInArabic(),
            'rejection_reason' => $this->rejection_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'rating' => $this->rating,
            'review' => $this->review,
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_rated' => $this->canBeRated(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function getStatusInArabic()
    {
        return match($this->status) {
            'pending' => 'قيد المراجعة',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => $this->status,
        };
    }
}
