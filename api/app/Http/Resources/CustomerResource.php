<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'sales_count' => $this->whenCounted('sales'),
            'last_purchase_at' => $this->when(
                isset($this->last_purchase_at),
                fn () => $this->last_purchase_at,
            ),
            'total_purchased' => $this->when(
                isset($this->total_purchased),
                fn () => $this->total_purchased,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
