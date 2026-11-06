<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RejectPublicOrderRequest;
use App\Http\Resources\PublicOrderResource;
use App\Models\PublicOrder;
use App\Services\Farm\PublicOrderService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicOrderController extends Controller
{
    public function __construct(
        protected PublicOrderService $orders,
        protected TenantContext $tenant,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PublicOrder::query()
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return PublicOrderResource::collection($query);
    }

    public function summary(): JsonResponse
    {
        $base = PublicOrder::query();
        $organization = $this->tenant->organization();

        return response()->json([
            'data' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pendiente')->count(),
                'approved' => (clone $base)->where('status', 'aprobado')->count(),
                'rejected' => (clone $base)->where('status', 'rechazado')->count(),
                'pending_amount' => round((float) (clone $base)
                    ->where('status', 'pendiente')
                    ->sum('total'), 2),
                'today' => (clone $base)->whereDate('created_at', now()->toDateString())->count(),
                'public_url' => $organization ? "/catalogo/{$organization->slug}" : null,
                'organization_name' => $organization?->name,
            ],
        ]);
    }

    public function show(PublicOrder $publicOrder): JsonResponse
    {
        $publicOrder->load(['items.product', 'sale', 'reviewer']);

        return (new PublicOrderResource($publicOrder))->response();
    }

    public function approve(Request $request, PublicOrder $publicOrder): JsonResponse
    {
        $order = $this->orders->approve($publicOrder, $request->user());

        return (new PublicOrderResource($order))->response();
    }

    public function reject(RejectPublicOrderRequest $request, PublicOrder $publicOrder): JsonResponse
    {
        $order = $this->orders->reject(
            $publicOrder,
            $request->user(),
            $request->validated('reason'),
        );

        return (new PublicOrderResource($order))->response();
    }

    public function receipt(PublicOrder $publicOrder): StreamedResponse|JsonResponse
    {
        if (! $publicOrder->receipt_path || ! Storage::disk(PublicOrderService::RECEIPT_DISK)->exists($publicOrder->receipt_path)) {
            return response()->json(['message' => 'El pedido no tiene comprobante.'], 404);
        }

        return Storage::disk(PublicOrderService::RECEIPT_DISK)->download(
            $publicOrder->receipt_path,
            $publicOrder->receipt_name ?: "comprobante-{$publicOrder->code}",
        );
    }
}
