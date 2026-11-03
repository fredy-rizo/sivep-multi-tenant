<?php

namespace App\Http\Resources;

use App\Models\AnimalMonthlySummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnimalMonthlySummary */
class AnimalMonthlySummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->period,
            'last_weight_kg' => $this->last_weight_kg,
            'avg_weight_kg' => $this->avg_weight_kg,
            'weight_gain_kg' => $this->weight_gain_kg,
            'daily_gain_g' => $this->daily_gain_g,
            'events_count' => $this->events_count,
            'vaccines_count' => $this->vaccines_count,
            'notes' => $this->notes,
        ];
    }
}
