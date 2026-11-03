<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sortable = ['name', 'document_number', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $customers = Customer::query()
            ->withCount(['sales as sales_count' => fn ($query) => $query->where('status', '!=', 'anulada')])
            ->withMax(['sales as last_purchase_at' => fn ($query) => $query->where('status', '!=', 'anulada')], 'sale_date')
            ->withSum(['sales as total_purchased' => fn ($query) => $query->where('status', '!=', 'anulada')], 'total')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 25), 100))
            ->withQueryString();

        return CustomerResource::collection($customers);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total' => Customer::query()->count(),
                'active' => Customer::query()->where('is_active', true)->count(),
                'with_purchases' => Customer::query()
                    ->whereHas('sales', fn ($query) => $query->where('status', '!=', 'anulada'))
                    ->count(),
                'total_sold' => round((float) Sale::query()
                    ->where('status', '!=', 'anulada')
                    ->sum('total'), 2),
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->loadCount(['sales as sales_count' => fn ($query) => $query->where('status', '!=', 'anulada')]);
        $customer->loadSum(['sales as total_purchased' => fn ($query) => $query->where('status', '!=', 'anulada')], 'total');

        $sales = $customer->sales()
            ->with(['items.product', 'items.animal', 'creator'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => new CustomerResource($customer),
            'sales' => SaleResource::collection($sales),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return (new CustomerResource($customer))->response();
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->update(['is_active' => false]);

        return response()->json(['message' => 'Cliente desactivado.']);
    }
}
