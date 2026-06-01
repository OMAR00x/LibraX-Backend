<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->first_name . ' ' . $this->last_name,
            'phone' => $this->phone,
            'role' => $this->role,
            'role_ar' => $this->getRoleInArabic(),
            'avatar' => $this->avatar ? url('storage/' . $this->avatar) : null,
            'library_name' => $this->library_name,
            'library_address' => $this->library_address,
            'library_latitude' => $this->library_latitude,
            'library_longitude' => $this->library_longitude,
            'library_image' => $this->library_image ? url('storage/' . $this->library_image) : null,
            'library_description' => $this->library_description,
            'wallet_balance' => (float) $this->wallet_balance,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function getRoleInArabic()
    {
        return match($this->role) {
            'admin' => 'أدمن',
            'library_owner' => 'صاحب مكتبة',
            'customer' => 'زبون',
            default => $this->role,
        };
    }
}
