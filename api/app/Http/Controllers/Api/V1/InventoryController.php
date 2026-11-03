<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Farm\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $movements = InventoryMovement::query()
            ->with(['product', 'sale', 'creator'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('movement_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('movement_date', '<=', $request->input('to')))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return InventoryMovementResource::collection($movements);
    }

    public function store(StoreInventoryMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = Product::query()->findOrFail($data['product_id']);

        $movement = $this->inventory->register($product, $data, $request->user()->id);

        return (new InventoryMovementResource($movement))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function summary(): JsonResponse
    {
        $productQuery = Product::query()->where('is_active', true);

        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'products' => $productQuery->count(),
                'units' => round((float) Product::query()->where('is_active', true)->sum('stock'), 2),
                'inventory_value' => round((float) Product::query()
                    ->where('is_active', true)
                    ->sum(DB::raw('stock * COALESCE(cost, price)')), 2),
                'low_stock_count' => Product::query()
                    ->where('is_active', true)
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->count(),
                'movements_this_month' => InventoryMovement::query()
                    ->whereBetween('movement_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->count(),
                'low_stock' => ProductResource::collection($lowStock),
            ],
        ]);
    }
}
