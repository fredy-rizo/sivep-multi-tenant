<?php

namespace App\Http\Resources;

use App\Models\AnimalEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnimalEvent */
class AnimalEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_date' => $this->event_date?->toDateString(),
            'type' => $this->whenLoaded('eventType', fn () => [
                'id' => $this->eventType->id,
                'code' => $this->eventType->code,
                'name' => $this->eventType->name,
                'category' => $this->eventType->category,
            ]),
            'weight_kg' => $this->weight_kg,
            'vaccine' => $this->whenLoaded('vaccine', fn () => $this->vaccine ? [
                'id' => $this->vaccine->id,
                'name' => $this->vaccine->name,
                'disease' => $this->vaccine->disease,
            ] : null),
            'dose_ml' => $this->dose_ml,
            'batch_number' => $this->batch_number,
            'next_due_date' => $this->next_due_date?->toDateString(),
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'cost' => $this->cost,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
