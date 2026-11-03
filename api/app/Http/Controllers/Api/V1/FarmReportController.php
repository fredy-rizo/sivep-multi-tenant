<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FarmReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $sales = Sale::query()->where('status', '!=', 'anulada');

        $monthSales = Sale::query()
            ->where('status', '!=', 'anulada')
            ->whereBetween('sale_date', [$monthStart, $monthEnd]);

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', '!=', 'anulada')
            ->whereDate('sales.sale_date', '>=', now()->subDays(90)->toDateString())
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.line_total) as total'),
            )
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $trend = Sale::query()
            ->where('status', '!=', 'anulada')
            ->whereDate('sale_date', '>=', now()->subMonths(5)->startOfMonth()->toDateString())
            ->select(
                DB::raw("DATE_FORMAT(sale_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total'),
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'sales' => [
                    'today_total' => round((float) (clone $sales)
                        ->whereDate('sale_date', now()->toDateString())
                        ->sum('total'), 2),
                    'month_total' => round((float) (clone $monthSales)->sum('total'), 2),
                    'month_count' => (clone $monthSales)->count(),
                    'average_ticket' => round((float) (clone $monthSales)->avg('total'), 2),
                    'pending_count' => Sale::query()->where('status', 'pendiente')->count(),
                ],
                'inventory' => [
                    'products' => Product::query()->where('is_active', true)->count(),
                    'units' => round((float) Product::query()->where('is_active', true)->sum('stock'), 2),
                    'value' => round((float) Product::query()
                        ->where('is_active', true)
                        ->sum(DB::raw('stock * COALESCE(cost, price)')), 2),
                    'low_stock_count' => Product::query()
                        ->where('is_active', true)
                        ->whereColumn('stock', '<=', 'min_stock')
                        ->count(),
                ],
                'top_products' => $topProducts,
                'sales_trend' => $trend,
                'low_stock' => ProductResource::collection($lowStock),
                'recent_sales' => SaleResource::collection(
                    Sale::query()
                        ->with('customer')
                        ->orderByDesc('sale_date')
                        ->orderByDesc('id')
                        ->limit(8)
                        ->get(),
                ),
            ],
        ]);
    }
}
