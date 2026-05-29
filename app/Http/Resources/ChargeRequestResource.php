<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->last_name,
                'phone' => $this->user->phone,
                'role' => $this->user->role,
            ] : null,
            'amount' => (float) $this->amount,
            'transaction_number' => $this->transaction_number,
            'receipt_image' => $this->receipt_image ? url('storage/' . $this->receipt_image) : null,
            'status' => $this->status,
            'status_ar' => $this->getStatusInArabic(),
            'rejection_reason' => $this->rejection_reason,
            'approved_by' => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->first_name . ' ' . $this->approvedBy->last_name,
            ] : null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function getStatusInArabic()
    {
        return match($this->status) {
            'pending' => 'قيد المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            default => $this->status,
        };
    }
}
