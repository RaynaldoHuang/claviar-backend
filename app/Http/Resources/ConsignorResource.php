<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasDeletionCounts = isset($this->products_count, $this->payouts_count, $this->intake_batches_count);

        return ['id' => $this->id, 'name' => $this->name, 'phone' => $this->phone, 'email' => $this->email, 'address' => $this->address, 'notes' => $this->notes, 'products_count' => $this->whenCounted('products'), 'stock_count' => $this->when(isset($this->stock_count), $this->stock_count), 'sold_count' => $this->when(isset($this->sold_count), $this->sold_count), 'payouts_count' => $this->whenCounted('payouts'), 'intake_batches_count' => $this->whenCounted('intakeBatches'), 'can_delete' => $this->when($hasDeletionCounts, fn () => $this->products_count === 0 && $this->payouts_count === 0 && $this->intake_batches_count === 0), 'products' => ProductResource::collection($this->whenLoaded('products')), 'created_at' => $this->created_at];
    }
}
