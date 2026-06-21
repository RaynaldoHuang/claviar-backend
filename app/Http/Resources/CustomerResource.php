<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'phone' => $this->phone, 'notes' => $this->notes,
            'purchases_count' => $this->sales_count ?? $this->whenCounted('sales'),
            'total_spent' => (float) ($this->sales_sum_sale_price ?? 0), 'last_purchase_at' => $this->sales_max_sold_at ?? null,
            'purchases' => $this->whenLoaded('sales', fn () => $this->sales->map(function ($sale) use ($request) {
                $image = $sale->product->images->firstWhere('is_cover', true) ?? $sale->product->images->first();
                return ['id' => $sale->id, 'product' => ['id' => $sale->product->id, 'code' => $sale->product->code, 'name' => $sale->product->name, 'category' => $sale->product->category?->name, 'brand' => $sale->product->brand?->name, 'image' => $image ? $request->getSchemeAndHttpHost().Storage::url($image->image) : null], 'sale_price' => (float) $sale->sale_price, 'payment_method' => $sale->payment_method, 'sold_at' => $sale->sold_at];
            })),
            'created_at' => $this->created_at,
        ];
    }
}
