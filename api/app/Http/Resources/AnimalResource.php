<?php

namespace App\Http\Resources;

use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Animal */
class AnimalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'tag' => $this->tag,
            'rfid' => $this->rfid,
            'name' => $this->name,
            'species' => $this->whenLoaded('species', fn () => [
                'id' => $this->species->id,
                'code' => $this->species->code,
                'name' => $this->species->name,
            ]),
            'breed' => $this->whenLoaded('breed', fn () => $this->breed ? [
                'id' => $this->breed->id,
                'code' => $this->breed->code,
                'name' => $this->breed->name,
            ] : null),
            'lot' => $this->whenLoaded('lot', fn () => $this->lot ? [
                'id' => $this->lot->id,
                'code' => $this->lot->code,
                'name' => $this->lot->name,
            ] : null),
            'sex' => $this->sex,
            'birth_date' => $this->birth_date?->toDateString(),
            'age_months' => $this->age_months,
            'birth_weight_kg' => $this->birth_weight_kg,
            'dam' => $this->whenLoaded('dam', fn () => $this->dam ? [
                'id' => $this->dam->id,
                'code' => $this->dam->code,
                'name' => $this->dam->name,
            ] : null),
            'sire' => $this->whenLoaded('sire', fn () => $this->sire ? [
                'id' => $this->sire->id,
                'code' => $this->sire->code,
                'name' => $this->sire->name,
            ] : null),
            'status' => $this->status,
            'current_weight_kg' => $this->current_weight_kg,
            'last_weight_at' => $this->last_weight_at?->toDateString(),
            'next_vaccine_at' => $this->when(isset($this->next_vaccine_at), fn () => $this->next_vaccine_at),
            'photo_path' => $this->photo_path,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
