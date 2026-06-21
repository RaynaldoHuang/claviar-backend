<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CheckoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage sales'), 403);

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $consignorId = $request->integer('consignor_id');
        $search = $request->string('search')->trim()->value();
        $from = $request->input('from');
        $to = $request->input('to');

        $orders = Order::query()
            ->with(['customer', 'sales.product.consignor', 'sales.product.images'])
            ->where('status', 'paid')
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->where('code', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))))
            ->when($consignorId, fn ($query) => $query->whereHas('sales.product', fn ($products) => $products->where('consignor_id', $consignorId)))
            ->when($from, fn ($query) => $query->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('paid_at', '<=', $to))
            ->get()
            ->map(fn (Order $order) => [
                'id' => 'order-'.$order->id,
                'code' => $order->code,
                'customer' => ['id' => $order->customer->id, 'name' => $order->customer->name, 'phone' => $order->customer->phone],
                'items_count' => $order->sales->count(),
                'total_amount' => (float) $order->sales->sum('sale_price'),
                'payment_method' => $order->payment_method,
                'paid_at' => $order->paid_at,
                'items' => $order->sales->map(fn (Sale $sale) => $this->item($sale, $request))->values(),
            ]);

        $legacy = Sale::query()
            ->with(['customer', 'product.consignor', 'product.images'])
            ->whereNull('order_id')
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->where('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")))
            ->when($consignorId, fn ($query) => $query->whereHas('product', fn ($products) => $products->where('consignor_id', $consignorId)))
            ->when($from, fn ($query) => $query->whereDate('sold_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('sold_at', '<=', $to))
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => 'sale-'.$sale->id,
                'code' => 'SALE-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT),
                'customer' => ['id' => $sale->customer_id, 'name' => $sale->customer?->name ?? $sale->customer_name, 'phone' => $sale->customer?->phone ?? $sale->customer_phone],
                'items_count' => 1,
                'total_amount' => (float) $sale->sale_price,
                'payment_method' => $sale->payment_method,
                'paid_at' => $sale->sold_at,
                'items' => [$this->item($sale, $request)],
            ]);

        return response()->json(['data' => $orders->concat($legacy)->sortByDesc('paid_at')->values()]);
    }

    private function item(Sale $sale, Request $request): array
    {
        $image = $sale->product->images->firstWhere('is_cover', true) ?? $sale->product->images->first();

        return [
            'id' => $sale->id,
            'product' => [
                'id' => $sale->product->id,
                'code' => $sale->product->code,
                'name' => $sale->product->name,
                'consignor' => $sale->product->consignor?->name,
                'image' => $image ? $request->getSchemeAndHttpHost().Storage::url($image->image) : null,
            ],
            'sale_price' => (float) $sale->sale_price,
        ];
    }
}
