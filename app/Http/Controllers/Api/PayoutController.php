<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSettlementRequest;
use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayoutController extends Controller
{
    public function outstanding(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage payouts'), 403);
        $sales = Sale::with(['product.consignor', 'product.category', 'product.brand', 'product.images'])
            ->whereNull('payout_id')->whereHas('product', fn ($query) => $query->whereNotNull('purchase_price'))
            ->latest('sold_at')->get();

        $data = $sales->groupBy('product.consignor_id')->map(function ($items) use ($request) {
            $consignor = $items->first()->product->consignor;
            return [
                'consignor' => ['id' => $consignor->id, 'name' => $consignor->name, 'phone' => $consignor->phone],
                'total_amount' => (float) $items->sum(fn ($sale) => $sale->product->purchase_price),
                'total_sale_value' => (float) $items->sum('sale_price'), 'items_count' => $items->count(),
                'items' => $items->map(fn ($sale) => $this->saleItem($sale, $request))->values(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function index(Request $request): mixed
    {
        return PayoutResource::collection(Payout::with(['consignor', 'sales.product.images'])->withCount('sales')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(CreateSettlementRequest $request): PayoutResource
    {
        $payout = DB::transaction(function () use ($request) {
            $sales = Sale::with('product')->whereNull('payout_id')
                ->whereHas('product', fn ($query) => $query->where('consignor_id', $request->integer('consignor_id'))->whereNotNull('purchase_price'))
                ->lockForUpdate()->get();
            abort_if($sales->isEmpty(), 422, 'Tidak ada penjualan yang belum dibayar untuk consignor ini.');
            $payout = Payout::create(['consignor_id' => $request->integer('consignor_id'), 'amount' => $sales->sum(fn ($sale) => $sale->product->purchase_price), 'status' => 'pending', 'notes' => $request->input('notes')]);
            Sale::whereIn('id', $sales->pluck('id'))->update(['payout_id' => $payout->id]);
            return $payout;
        });
        return new PayoutResource($payout->load(['consignor', 'sales.product.images'])->loadCount('sales'));
    }

    public function show(Payout $payout): PayoutResource { return new PayoutResource($payout->load(['consignor', 'sales.product.images'])->loadCount('sales')); }
    public function update(Request $request, Payout $payout): PayoutResource { abort_if($payout->status === 'paid', 422, 'Payout yang sudah dibayar tidak dapat diubah.'); $payout->update($request->validate(['notes' => ['nullable', 'string', 'max:2000']])); return new PayoutResource($payout->fresh()->load(['consignor', 'sales.product.images'])->loadCount('sales')); }
    public function markPaid(Request $request, Payout $payout): PayoutResource { abort_unless($request->user()->can('manage payouts'), 403); $payout->update(['status' => 'paid', 'paid_at' => now()]); return new PayoutResource($payout->fresh()->load(['consignor', 'sales.product.images'])->loadCount('sales')); }
    public function destroy(Payout $payout): mixed { abort_if($payout->status === 'paid', 422, 'Payout yang sudah dibayar tidak dapat dihapus.'); DB::transaction(function () use ($payout) { $payout->sales()->update(['payout_id' => null]); $payout->delete(); }); return response()->noContent(); }

    private function saleItem(Sale $sale, Request $request): array
    {
        $image = $sale->product->images->firstWhere('is_cover', true) ?? $sale->product->images->first();
        return ['sale_id' => $sale->id, 'product_id' => $sale->product->id, 'code' => $sale->product->code, 'name' => $sale->product->name, 'image' => $image ? $request->getSchemeAndHttpHost().Storage::url($image->image) : null, 'category' => $sale->product->category?->name, 'brand' => $sale->product->brand?->name, 'consignor_price' => (float) $sale->product->purchase_price, 'sale_price' => (float) $sale->sale_price, 'profit' => (float) ($sale->sale_price - $sale->product->purchase_price), 'customer_name' => $sale->customer_name, 'customer_phone' => $sale->customer_phone, 'payment_method' => $sale->payment_method, 'sold_at' => $sale->sold_at];
    }
}
