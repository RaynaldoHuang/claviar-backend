<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function store(CustomerRequest $request): CustomerResource
    {
        return new CustomerResource(Customer::create($request->validated()));
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());
        return new CustomerResource($customer->fresh()->loadCount('sales')->loadSum('sales', 'sale_price')->loadMax('sales', 'sold_at'));
    }

    public function destroy(Request $request, Customer $customer): mixed
    {
        abort_unless($request->user()->can('manage sales'), 403);
        abort_if($customer->sales()->exists(), 422, 'Customer yang memiliki transaksi tidak dapat dihapus.');
        $customer->delete();
        return response()->noContent();
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('manage sales'), 403);
        $salesFilter = fn ($query) => $query->when(
            $request->integer('consignor_id'),
            fn ($sales, $consignorId) => $sales->whereHas('product', fn ($products) => $products->where('consignor_id', $consignorId))
        );

        return CustomerResource::collection(Customer::query()
            ->withCount(['sales' => $salesFilter])
            ->withSum(['sales' => $salesFilter], 'sale_price')
            ->withMax(['sales' => $salesFilter], 'sold_at')
            ->when($request->integer('consignor_id'), fn ($query, $consignorId) => $query->whereHas('sales.product', fn ($products) => $products->where('consignor_id', $consignorId)))
            ->when($request->search, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('sales_max_sold_at')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        abort_unless($request->user()->can('manage sales'), 403);
        return new CustomerResource($customer->load(['sales' => fn ($query) => $query->with(['product.category', 'product.brand', 'product.images'])->latest('sold_at')])->loadCount('sales')->loadSum('sales', 'sale_price')->loadMax('sales', 'sold_at'));
    }
}
