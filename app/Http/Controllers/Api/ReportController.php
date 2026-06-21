<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consignor;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['sales', 'revenue', 'profit', 'product-status', 'consignors']), 404);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $sales = Sale::with(['product.consignor', 'product.category', 'product.brand'])
            ->when($request->from, fn ($query, $from) => $query->whereDate('sold_at', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->whereDate('sold_at', '<=', $to))
            ->latest('sold_at')->get();

        $data = match ($type) {
            'sales' => $sales,
            'revenue' => ['total' => (float) $sales->sum('sale_price'), 'transactions' => $sales->count(), 'items' => $sales],
            'profit' => ['revenue' => (float) $sales->sum('sale_price'), 'cost' => (float) $sales->sum(fn ($sale) => $sale->product->purchase_price), 'profit' => (float) $sales->sum(fn ($sale) => $sale->sale_price - $sale->product->purchase_price), 'items' => $sales],
            'product-status' => Product::selectRaw("CASE WHEN is_draft = 1 THEN 'waiting-for-sale' ELSE status END as status, count(*) as total")->groupByRaw("CASE WHEN is_draft = 1 THEN 'waiting-for-sale' ELSE status END")->get(),
            'consignors' => Consignor::withCount('products')->withSum('payouts', 'amount')->get(),
        };

        return response()->json(['data' => $data, 'filters' => $request->only(['from', 'to'])]);
    }
}
