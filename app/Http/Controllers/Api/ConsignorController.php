<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsignorRequest;
use App\Http\Resources\ConsignorResource;
use App\Models\Consignor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsignorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection { return ConsignorResource::collection(Consignor::query()->withCount(['products', 'products as stock_count' => fn ($q) => $q->whereIn('status', ['available', 'reserved']), 'products as sold_count' => fn ($q) => $q->where('status', 'sold')])->when($request->search, fn ($q, $search) => $q->where(fn ($n) => $n->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->latest()->paginate($request->integer('per_page', 15))->withQueryString()); }
    public function store(ConsignorRequest $request): ConsignorResource { return new ConsignorResource(Consignor::create($request->validated())); }
    public function show(Consignor $consignor): ConsignorResource { return new ConsignorResource($consignor->load(['products.category', 'products.brand'])->loadCount('products')); }
    public function update(ConsignorRequest $request, Consignor $consignor): ConsignorResource { $consignor->update($request->validated()); return new ConsignorResource($consignor->fresh()->loadCount('products')); }
    public function destroy(Consignor $consignor): mixed { abort_if($consignor->products()->exists(), 422, 'Consignor masih memiliki produk.'); $consignor->delete(); return response()->noContent(); }
}
