<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAnimalEventRequest;
use App\Http\Resources\AnimalEventResource;
use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Services\Livestock\AnimalEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AnimalEventController extends Controller
{
    public function __construct(protected AnimalEventService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $events = AnimalEvent::query()
            ->with(['animal', 'eventType', 'vaccine', 'creator'])
            ->when($request->filled('animal_id'), fn ($query) => $query->where('animal_id', $request->integer('animal_id')))
            ->when($request->filled('event_type_id'), fn ($query) => $query->where('event_type_id', $request->integer('event_type_id')))
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('eventType', fn ($query) => $query->where('category', $request->input('category')));
            })
            ->when($request->filled('from'), fn ($query) => $query->whereDate('event_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('event_date', '<=', $request->date('to')))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 50), 200))
            ->withQueryString();

        return AnimalEventResource::collection($events);
    }

    public function store(StoreAnimalEventRequest $request, Animal $animal): JsonResponse
    {
        $event = $this->service->record(
            $animal,
            $request->validated(),
            $request->user()->id,
        );

        return (new AnimalEventResource($event))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(AnimalEvent $event): Response
    {
        $this->service->delete($event);

        return response()->noContent();
    }
}
