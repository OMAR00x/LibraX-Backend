<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'icon' => $this->icon && !is_numeric($this->icon) && (strpos($this->icon, '/') !== false || strpos($this->icon, '.') !== false) 
                ? url('storage/' . $this->icon) 
                : $this->icon,
            'is_active' => $this->is_active,
            'books_count' => $this->whenCounted('books'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
