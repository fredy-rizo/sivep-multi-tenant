<?php

namespace App\Services\Livestock;

use App\Models\Animal;
use App\Models\AnimalMonthlySummary;
use Illuminate\Support\Carbon;

class MonthlySummaryService
{
    /**
     * Recalcula el resumen de un animal para un periodo YYYY-MM.
     * Es idempotente: puede ejecutarse tantas veces como sea necesario.
     */
    public function recalculate(Animal $animal, string $period): AnimalMonthlySummary
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = $animal->events()
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $weights = $events
            ->whereNotNull('weight_kg')
            ->sortBy('event_date')
            ->values();

        $lastWeight = $weights->last();
        $avgWeight = $weights->isNotEmpty() ? round((float) $weights->avg('weight_kg'), 2) : null;

        $previous = $animal->events()
            ->whereNotNull('weight_kg')
            ->whereDate('event_date', '<', $start->toDateString())
            ->orderByDesc('event_date')
            ->first();

        $gain = null;
        $dailyGain = null;

        if ($lastWeight && $previous) {
            $gain = round((float) $lastWeight->weight_kg - (float) $previous->weight_kg, 2);
            $days = $previous->event_date->diffInDays($lastWeight->event_date);

            if ($days > 0) {
                $dailyGain = round(($gain * 1000) / $days, 2);
            }
        }

        return AnimalMonthlySummary::updateOrCreate(
            [
                'animal_id' => $animal->id,
                'period' => $period,
            ],
            [
                'organization_id' => $animal->organization_id,
                'last_weight_kg' => $lastWeight?->weight_kg,
                'avg_weight_kg' => $avgWeight,
                'weight_gain_kg' => $gain,
                'daily_gain_g' => $dailyGain,
                'events_count' => $events->count(),
                'vaccines_count' => $events->whereNotNull('vaccine_id')->count(),
            ],
        );
    }
}
