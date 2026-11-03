<?php

namespace App\Services\Livestock;

use App\Models\Animal;
use App\Models\AnimalEvent;
use Illuminate\Support\Facades\DB;

class AnimalEventService
{
    public function __construct(
        protected MonthlySummaryService $monthlySummary,
    ) {}

    /**
     * Registra un evento y aplica sus efectos sobre el animal y los resúmenes.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(Animal $animal, array $data, ?int $userId = null): AnimalEvent
    {
        return DB::transaction(function () use ($animal, $data, $userId) {
            $event = $animal->events()->create([
                ...$data,
                'organization_id' => $animal->organization_id,
                'created_by' => $userId,
            ]);

            $event->load('eventType');

            $this->applySideEffects($animal, $event);

            $period = $event->event_date->format('Y-m');
            $this->monthlySummary->recalculate($animal, $period);

            $currentPeriod = now()->format('Y-m');
            if ($currentPeriod !== $period) {
                $this->monthlySummary->recalculate($animal, $currentPeriod);
            }

            return $event->load(['eventType', 'vaccine', 'creator']);
        });
    }

    public function delete(AnimalEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $animal = $event->animal;
            $period = $event->event_date->format('Y-m');

            $event->delete();

            $latestWeight = $animal->events()
                ->whereNotNull('weight_kg')
                ->orderByDesc('event_date')
                ->first();

            $animal->forceFill([
                'current_weight_kg' => $latestWeight?->weight_kg,
                'last_weight_at' => $latestWeight?->event_date,
            ])->save();

            $this->monthlySummary->recalculate($animal, $period);
            $this->monthlySummary->recalculate($animal, now()->format('Y-m'));
        });
    }

    protected function applySideEffects(Animal $animal, AnimalEvent $event): void
    {
        $type = $event->eventType;

        if ($event->weight_kg !== null) {
            $isLatest = ! $animal->last_weight_at
                || $event->event_date->greaterThanOrEqualTo($animal->last_weight_at);

            if ($isLatest) {
                $animal->forceFill([
                    'current_weight_kg' => $event->weight_kg,
                    'last_weight_at' => $event->event_date,
                ])->save();
            }
        }

        if ($type?->category === 'movimiento') {
            $toLotId = data_get($event->metadata, 'to_lot_id');

            if ($toLotId) {
                $animal->forceFill(['lot_id' => $toLotId])->save();
            }
        }

        $status = match ($type?->code) {
            'venta' => 'vendido',
            'muerte' => 'fallecido',
            'parto' => 'lactando',
            'diagnostico_gestacion' => data_get($event->metadata, 'pregnant', true) ? 'preñada' : 'activo',
            default => null,
        };

        if ($status) {
            $animal->forceFill(['status' => $status])->save();
        }
    }
}
