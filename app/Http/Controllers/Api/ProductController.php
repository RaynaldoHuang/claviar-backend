<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductImageService $images) {}
    public function index(Request $request): AnonymousResourceCollection { return ProductResource::collection(Product::query()->with(['consignor', 'category', 'brand', 'images', 'sale.customer'])->search($request->search)->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))->when($request->consignor_id, fn ($q, $v) => $q->where('consignor_id', $v))->when($request->has('is_draft'), fn ($q) => $q->where('is_draft', $request->boolean('is_draft')))->latest()->paginate($request->integer('per_page', 15))->withQueryString()); }
    public function store(ProductRequest $request): ProductResource { $product = Product::create($request->safe()->except(['images', 'cover_index'])); if ($request->hasFile('images')) $this->images->store($product, $request->file('images'), $request->integer('cover_index')); return new ProductResource($product->load(['consignor', 'category', 'brand', 'images'])); }
    public function show(Product $product): ProductResource { return new ProductResource($product->load(['consignor', 'category', 'brand', 'images', 'sale'])); }
    public function update(ProductRequest $request, Product $product): ProductResource { $product->update($request->safe()->except(['images', 'cover_index'])); if ($request->hasFile('images')) $this->images->store($product, $request->file('images'), $request->integer('cover_index')); return new ProductResource($product->fresh()->load(['consignor', 'category', 'brand', 'images'])); }
    public function destroy(Product $product): mixed { abort_if($product->sale()->exists(), 422, 'Produk yang sudah terjual tidak dapat dihapus.'); abort_if($product->orderItem()->exists(), 422, 'Kartu yang sudah masuk order tidak dapat dihapus. Batalkan order terlebih dahulu.'); $this->images->delete($product); $product->delete(); return response()->noContent(); }
}
