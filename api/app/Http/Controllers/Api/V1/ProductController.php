<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Farm\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sortable = ['sku', 'name', 'category', 'price', 'stock', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->boolean('low_stock'), fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $initialStock = (float) ($data['stock'] ?? 0);
        unset($data['stock']);

        $product = Product::create([...$data, 'stock' => 0]);

        if ($initialStock > 0) {
            $this->inventory->register($product, [
                'type' => 'entrada',
                'quantity' => $initialStock,
                'unit_cost' => $data['cost'] ?? null,
                'reason' => 'Stock inicial',
                'movement_date' => now()->toDateString(),
            ], $request->user()->id);
        }

        return (new ProductResource($product->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product))->response();
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false]);

        return response()->json(['message' => 'Producto desactivado.']);
    }
}
