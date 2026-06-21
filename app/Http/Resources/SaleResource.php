<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'product' => new ProductResource($this->whenLoaded('product')), 'customer_name' => $this->customer_name, 'customer_phone' => $this->customer_phone, 'sale_price' => (float) $this->sale_price, 'payment_method' => $this->payment_method, 'sold_at' => $this->sold_at]; }
}
