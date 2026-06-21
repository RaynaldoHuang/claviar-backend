<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Consignor;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $monthly = Sale::with('product')->whereBetween('sold_at', [now()->startOfMonth(), now()])->get();
        $trend = collect(range(5, 0))->map(function (int $offset) {
            $month = now()->subMonths($offset);
            $sales = Sale::with('product')->whereBetween('sold_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->get();

            return ['month' => $month->translatedFormat('M'), 'revenue' => (float) $sales->sum('sale_price'), 'profit' => (float) $sales->sum(fn ($sale) => $sale->sale_price - $sale->product->purchase_price)];
        });

        return response()->json(['data' => ['total_products' => Product::count(), 'available_products' => Product::where('status', 'available')->count(), 'draft_products' => Product::where('is_draft', true)->count(), 'sold_products' => Product::where('status', 'sold')->count(), 'total_consignors' => Consignor::count(), 'monthly_revenue' => (float) $monthly->sum('sale_price'), 'monthly_profit' => (float) $monthly->sum(fn ($sale) => $sale->sale_price - $sale->product->purchase_price), 'trend' => $trend, 'recent_sales' => SaleResource::collection(Sale::with(['product.consignor'])->latest('sold_at')->limit(10)->get())]]);
    }
}
