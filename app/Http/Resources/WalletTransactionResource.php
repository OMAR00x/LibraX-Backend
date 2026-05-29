<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_ar' => $this->getTypeInArabic(),
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at->toISOString(),
            'date' => $this->created_at->format('d/m/Y'),
            'time' => $this->created_at->format('H:i'),
        ];
    }

    private function getTypeInArabic()
    {
        return match($this->type) {
            'charge' => 'شحن رصيد',
            'purchase' => 'شراء كتاب',
            'refund' => 'استرجاع',
            'earning' => 'ربح',
            default => $this->type,
        };
    }
}
