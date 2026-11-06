<?php

namespace App\Http\Resources;

use App\Models\PublicOrder;
use App\Models\PublicOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PublicOrder */
class PublicOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'customer_name' => $this->customer_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'total' => $this->total,
            'notes' => $this->notes,
            'has_receipt' => $this->has_receipt,
            'receipt_name' => $this->when($this->has_receipt, fn () => $this->receipt_name),
            'items_count' => $this->items_count
                ?? ($this->relationLoaded('items') ? $this->items->count() : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(
                fn (PublicOrderItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ],
            )),
            'sale' => $this->whenLoaded('sale', fn () => $this->sale ? [
                'id' => $this->sale->id,
                'code' => $this->sale->code,
                'total' => $this->sale->total,
                'status' => $this->sale->status,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
