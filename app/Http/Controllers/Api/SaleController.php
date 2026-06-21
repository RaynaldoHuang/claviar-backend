<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CustomerService;

class SaleController extends Controller
{
    public function __construct(private CustomerService $customers) {}
    public function index(Request $request): mixed { return SaleResource::collection(Sale::with(['product.consignor', 'product.category', 'product.brand'])->when($request->search, fn ($q, $v) => $q->where('customer_name', 'like', "%{$v}%"))->latest('sold_at')->paginate($request->integer('per_page', 15))); }
    public function store(SaleRequest $request): SaleResource { $sale = DB::transaction(function () use ($request) { $product = Product::lockForUpdate()->findOrFail($request->integer('product_id')); abort_if($product->is_draft, 422, 'Lengkapi kartu produk sebelum mencatat penjualan.'); abort_if($product->status === 'sold', 422, 'Produk sudah terjual.'); $data = $request->validated(); $customer = $this->customers->resolve($data['customer_name'], $data['customer_phone']); $data['customer_id'] = $customer->id; $data['customer_phone'] = $customer->phone; $sale = Sale::create($data); $product->update(['status' => 'sold']); return $sale; }); return new SaleResource($sale->load(['product.consignor', 'product.category', 'product.brand'])); }
    public function show(Sale $sale): SaleResource { return new SaleResource($sale->load(['product.consignor', 'product.category', 'product.brand'])); }
    public function update(SaleRequest $request, Sale $sale): SaleResource { $data = $request->validated(); $customer = $this->customers->resolve($data['customer_name'], $data['customer_phone']); $data['customer_id'] = $customer->id; $data['customer_phone'] = $customer->phone; $sale->update($data); return new SaleResource($sale->fresh()->load(['product.consignor', 'product.category', 'product.brand'])); }
    public function destroy(Request $request, Sale $sale): mixed { abort_unless($request->user()->hasRole('super-admin'), 403); DB::transaction(function () use ($sale) { $sale->product()->update(['status' => 'available']); $sale->delete(); }); return response()->noContent(); }
}
