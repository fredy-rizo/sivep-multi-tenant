<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\Farm\SaleService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SaleController extends Controller
{
    public function __construct(protected SaleService $sales) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = Sale::query()
            ->with(['customer', 'creator'])
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('sale_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('sale_date', '<=', $request->input('to')))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->sales->create([
            ...$request->validated(),
            'organization_id' => app(TenantContext::class)->id(),
        ], $request->user()->id);

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['customer', 'items.product', 'items.animal', 'creator']);

        return (new SaleResource($sale))->response();
    }

    public function cancel(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $sale = $this->sales->cancel($sale, $data['reason'] ?? null, $request->user()->id);

        return (new SaleResource($sale))->response();
    }
}
