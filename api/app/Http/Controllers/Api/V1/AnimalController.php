<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAnimalRequest;
use App\Http\Requests\Api\V1\UpdateAnimalRequest;
use App\Http\Resources\AnimalEventResource;
use App\Http\Resources\AnimalMonthlySummaryResource;
use App\Http\Resources\AnimalResource;
use App\Models\Animal;
use App\Services\Livestock\MonthlySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AnimalController extends Controller
{
    public function __construct(protected MonthlySummaryService $monthlySummary) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sortable = ['code', 'name', 'birth_date', 'current_weight_kg', 'status', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'code';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $animals = Animal::query()
            ->with(['species', 'breed', 'lot', 'dam', 'sire'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('tag', 'like', "%{$search}%")
                        ->orWhere('rfid', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('species_id'), fn ($query) => $query->where('species_id', $request->integer('species_id')))
            ->when($request->filled('breed_id'), fn ($query) => $query->where('breed_id', $request->integer('breed_id')))
            ->when($request->filled('lot_id'), fn ($query) => $query->where('lot_id', $request->integer('lot_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('sex'), fn ($query) => $query->where('sex', $request->input('sex')))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return AnimalResource::collection($animals);
    }

    public function store(StoreAnimalRequest $request): JsonResponse
    {
        $animal = Animal::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $animal->load(['species', 'breed', 'lot', 'dam', 'sire']);

        return (new AnimalResource($animal))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Animal $animal): JsonResponse
    {
        $animal->load(['species', 'breed', 'lot', 'dam', 'sire', 'creator']);

        $events = $animal->events()
            ->with(['eventType', 'vaccine', 'creator'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $summaries = $animal->monthlySummaries()
            ->orderByDesc('period')
            ->limit(24)
            ->get();

        $nextVaccine = $animal->events()
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '>=', now()->toDateString())
            ->orderBy('next_due_date')
            ->first();

        $animal->setAttribute('next_vaccine_at', $nextVaccine?->next_due_date?->toDateString());

        return response()->json([
            'data' => new AnimalResource($animal),
            'events' => AnimalEventResource::collection($events),
            'monthly_summaries' => AnimalMonthlySummaryResource::collection($summaries),
        ]);
    }

    public function update(UpdateAnimalRequest $request, Animal $animal): JsonResponse
    {
        $animal->update($request->validated());
        $animal->load(['species', 'breed', 'lot', 'dam', 'sire']);

        return (new AnimalResource($animal))->response();
    }

    public function destroy(Animal $animal): Response
    {
        $animal->delete();

        return response()->noContent();
    }

    public function timeline(Animal $animal): AnonymousResourceCollection
    {
        $events = $animal->events()
            ->with(['eventType', 'vaccine', 'creator'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        return AnimalEventResource::collection($events);
    }

    public function summaries(Animal $animal): AnonymousResourceCollection
    {
        $summaries = $animal->monthlySummaries()
            ->orderByDesc('period')
            ->limit(36)
            ->get();

        return AnimalMonthlySummaryResource::collection($summaries);
    }
}
