<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,

            // ✅ إخفاء البيانات الحساسة:
            // - password (مخفي تلقائياً)
            // - created_at, updated_at, deleted_at (مخفية)

            // إضافة بيانات محسوبة
            'full_name' => $this->first_name . ' ' . $this->last_name,

        ];
    }
}
