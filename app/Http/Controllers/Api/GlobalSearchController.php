<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consignor;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = $data['q'];
        $products = Product::with('consignor')->where(fn ($query) => $query->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))->latest()->limit(6)->get()
            ->map(fn ($product) => ['type' => 'product', 'title' => $product->name ?: 'Draft product', 'subtitle' => $product->code.' · '.$product->consignor?->name, 'url' => '/products?search='.urlencode($product->code)]);
        $consignors = Consignor::where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))->latest()->limit(5)->get()
            ->map(fn ($consignor) => ['type' => 'consignor', 'title' => $consignor->name, 'subtitle' => $consignor->phone ?: 'Consignor', 'url' => '/consignors?search='.urlencode($consignor->name)]);
        $customers = Customer::where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))->latest()->limit(5)->get()
            ->map(fn ($customer) => ['type' => 'customer', 'title' => $customer->name, 'subtitle' => $customer->phone ?: 'Customer', 'url' => '/customers?search='.urlencode($customer->name)]);
        $sales = Sale::with('product')->where(fn ($query) => $query->where('customer_name', 'like', "%{$term}%")->orWhere('customer_phone', 'like', "%{$term}%"))->latest('sold_at')->limit(5)->get()
            ->map(fn ($sale) => ['type' => 'sale', 'title' => $sale->customer_name, 'subtitle' => ($sale->product->name ?: $sale->product->code).' · Sale', 'url' => '/sales?search='.urlencode($sale->customer_name)]);

        return response()->json(['data' => $products->concat($consignors)->concat($customers)->concat($sales)->values()]);
    }
}
