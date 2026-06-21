<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteProductSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ProductImageService;
use Illuminate\Support\Facades\DB;

class CompleteProductSaleController extends Controller
{
    public function __construct(private ProductImageService $images) {}

    public function __invoke(CompleteProductSaleRequest $request, Product $product): SaleResource
    {
        $sale = DB::transaction(function () use ($request, $product) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            abort_unless($product->is_draft && $product->status === 'available', 422, 'Kartu produk ini sudah dilengkapi atau terjual.');
            $product->update([
                'name' => $request->string('name'), 'category_id' => $request->integer('category_id'),
                'brand_id' => $request->filled('brand_id') ? $request->integer('brand_id') : null,
                'condition' => $request->string('condition'), 'description' => $request->input('description'),
                'purchase_price' => $request->input('purchase_price'), 'selling_price' => $request->input('sale_price'),
                'status' => 'sold', 'is_draft' => false,
            ]);
            $this->images->store($product, $request->file('images'), $request->integer('cover_index'));
            $customer = Customer::findOrFail($request->integer('customer_id'));
            return Sale::create([
                'product_id' => $product->id, 'customer_name' => $customer->name,
                'customer_id' => $customer->id, 'customer_phone' => $customer->phone, 'sale_price' => $request->input('sale_price'),
                'payment_method' => $request->string('payment_method'), 'sold_at' => $request->date('sold_at') ?? now(),
            ]);
        });

        return new SaleResource($sale->load(['product.consignor', 'product.category', 'product.brand', 'product.images']));
    }
}
