<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'consignor' => new ConsignorResource($this->whenLoaded('consignor')), 'amount' => (float) $this->amount, 'status' => $this->status, 'paid_at' => $this->paid_at, 'notes' => $this->notes, 'items_count' => $this->whenCounted('sales'), 'items' => $this->whenLoaded('sales', fn () => $this->sales->map(fn ($sale) => ['sale_id' => $sale->id, 'product_id' => $sale->product->id, 'code' => $sale->product->code, 'name' => $sale->product->name, 'image' => optional($sale->product->images->firstWhere('is_cover', true) ?? $sale->product->images->first())->image ? $request->getSchemeAndHttpHost().\Illuminate\Support\Facades\Storage::url($sale->product->images->firstWhere('is_cover', true)?->image ?? $sale->product->images->first()?->image) : null, 'consignor_price' => (float) $sale->product->purchase_price, 'sale_price' => (float) $sale->sale_price, 'customer_name' => $sale->customer_name, 'sold_at' => $sale->sold_at])), 'created_at' => $this->created_at]; }
}
