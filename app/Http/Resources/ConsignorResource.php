<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignorResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'name' => $this->name, 'phone' => $this->phone, 'email' => $this->email, 'address' => $this->address, 'notes' => $this->notes, 'products_count' => $this->whenCounted('products'), 'stock_count' => $this->when(isset($this->stock_count), $this->stock_count), 'sold_count' => $this->when(isset($this->sold_count), $this->sold_count), 'products' => ProductResource::collection($this->whenLoaded('products')), 'created_at' => $this->created_at]; }
}
