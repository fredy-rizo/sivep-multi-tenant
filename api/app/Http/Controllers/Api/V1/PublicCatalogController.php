<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePublicOrderRequest;
use App\Http\Resources\PublicOrderResource;
use App\Models\Organization;
use App\Services\Farm\PublicOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PublicCatalogController extends Controller
{
    public function __construct(protected PublicOrderService $orders) {}

    public function show(string $slug): JsonResponse
    {
        $organization = $this->organization($slug);

        if (! $organization) {
            return response()->json(['message' => 'Catálogo no encontrado.'], 404);
        }

        if (! $this->orders->hasFeature($organization)) {
            return $this->featureNotIncluded();
        }

        return response()->json([
            'data' => $this->orders->catalog($organization),
        ]);
    }

    public function store(StorePublicOrderRequest $request, string $slug): JsonResponse
    {
        $organization = $this->organization($slug);

        if (! $organization) {
            return response()->json(['message' => 'Catálogo no encontrado.'], 404);
        }

        if (! $this->orders->hasFeature($organization)) {
            return $this->featureNotIncluded();
        }

        $order = $this->orders->create(
            $organization,
            $request->validated(),
            $request->file('receipt'),
        );

        return (new PublicOrderResource($order))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    protected function organization(string $slug): ?Organization
    {
        return Organization::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    protected function featureNotIncluded(): JsonResponse
    {
        return response()->json([
            'message' => 'Este catálogo no está disponible en este momento.',
            'code' => 'FEATURE_NOT_INCLUDED',
            'feature' => PublicOrderService::FEATURE,
        ], 402);
    }
}
