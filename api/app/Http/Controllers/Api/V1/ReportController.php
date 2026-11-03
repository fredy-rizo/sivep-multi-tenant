<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\AnimalMonthlySummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $byStatus = Animal::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $bySpecies = Animal::query()
            ->join('species', 'species.id', '=', 'animals.species_id')
            ->select('species.name', DB::raw('COUNT(*) as total'))
            ->groupBy('species.id', 'species.name')
            ->get();

        return response()->json([
            'data' => [
                'total_animals' => Animal::query()->count(),
                'active_animals' => Animal::query()->where('status', 'activo')->count(),
                'average_weight_kg' => round((float) Animal::query()->avg('current_weight_kg'), 2),
                'by_status' => $byStatus,
                'by_species' => $bySpecies,
                'upcoming_vaccines_30d' => AnimalEvent::query()
                    ->whereNotNull('next_due_date')
                    ->whereBetween('next_due_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->count(),
            ],
        ]);
    }

    public function weightEvolution(Request $request): JsonResponse
    {
        $months = min(max($request->integer('months', 12), 1), 36);

        $periods = AnimalMonthlySummary::query()
            ->select(
                'period',
                DB::raw('COUNT(DISTINCT animal_id) as animals'),
                DB::raw('AVG(avg_weight_kg) as avg_weight_kg'),
                DB::raw('SUM(weight_gain_kg) as total_gain_kg'),
                DB::raw('AVG(daily_gain_g) as avg_daily_gain_g'),
            )
            ->where('period', '>=', now()->subMonths($months - 1)->format('Y-m'))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json(['data' => $periods]);
    }

    public function upcomingVaccines(Request $request): JsonResponse
    {
        $days = min(max($request->integer('days', 30), 1), 365);

        $events = AnimalEvent::query()
            ->with(['animal', 'eventType', 'vaccine'])
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('next_due_date')
            ->limit(200)
            ->get()
            ->map(fn (AnimalEvent $event) => [
                'animal' => [
                    'id' => $event->animal?->id,
                    'code' => $event->animal?->code,
                    'name' => $event->animal?->name,
                ],
                'vaccine' => $event->vaccine?->name,
                'event_type' => $event->eventType?->name,
                'last_event_date' => $event->event_date?->toDateString(),
                'next_due_date' => $event->next_due_date?->toDateString(),
                'days_left' => (int) now()->startOfDay()->diffInDays($event->next_due_date, false),
            ]);

        return response()->json(['data' => $events]);
    }

    public function monthlyEvents(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);

        $events = AnimalEvent::query()
            ->select(
                DB::raw("DATE_FORMAT(event_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as total'),
            )
            ->whereYear('event_date', $year)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json(['data' => $events]);
    }
}
