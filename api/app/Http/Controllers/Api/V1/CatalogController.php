<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Breed;
use App\Models\EventType;
use App\Models\Lot;
use App\Models\Species;
use App\Models\Vaccine;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function __construct(protected TenantContext $tenant) {}

    public function species(): JsonResponse
    {
        $species = Species::query()
            ->availableTo($this->tenant->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'organization_id']);

        return response()->json(['data' => $species]);
    }

    public function breeds(Request $request): JsonResponse
    {
        $breeds = Breed::query()
            ->availableTo($this->tenant->id())
            ->where('is_active', true)
            ->when($request->filled('species_id'), fn ($query) => $query->where('species_id', $request->integer('species_id')))
            ->orderBy('name')
            ->get(['id', 'species_id', 'code', 'name', 'organization_id']);

        return response()->json(['data' => $breeds]);
    }

    public function lots(): JsonResponse
    {
        $lots = Lot::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $lots]);
    }

    public function storeLot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:40',
                Rule::unique('lots', 'code')->where('organization_id', $this->tenant->id()),
            ],
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $lot = Lot::create([...$data, 'organization_id' => $this->tenant->id()]);

        return response()->json(['data' => $lot], 201);
    }

    public function updateLot(Request $request, Lot $lot): JsonResponse
    {
        $data = $request->validate([
            'code' => [
                'sometimes', 'required', 'string', 'max:40',
                Rule::unique('lots', 'code')
                    ->where('organization_id', $this->tenant->id())
                    ->ignore($lot->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'area_hectares' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $lot->update($data);

        return response()->json(['data' => $lot]);
    }

    public function destroyLot(Lot $lot): JsonResponse
    {
        $lot->update(['is_active' => false]);

        return response()->json(['message' => 'Lote desactivado.']);
    }

    public function eventTypes(): JsonResponse
    {
        $types = EventType::query()
            ->availableTo($this->tenant->id())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category', 'requires_weight', 'requires_vaccine', 'organization_id']);

        return response()->json(['data' => $types]);
    }

    public function vaccines(): JsonResponse
    {
        $vaccines = Vaccine::query()
            ->availableTo($this->tenant->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'disease', 'manufacturer', 'default_dose_ml', 'interval_days', 'organization_id']);

        return response()->json(['data' => $vaccines]);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'species' => Species::query()->availableTo($this->tenant->id())->where('is_active', true)->count(),
            'breeds' => Breed::query()->availableTo($this->tenant->id())->where('is_active', true)->count(),
            'lots' => Lot::query()->where('is_active', true)->count(),
            'event_types' => EventType::query()->availableTo($this->tenant->id())->where('is_active', true)->count(),
            'vaccines' => Vaccine::query()->availableTo($this->tenant->id())->where('is_active', true)->count(),
        ]);
    }
}
