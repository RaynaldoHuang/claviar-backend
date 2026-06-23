<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasDeletionCounts = isset($this->products_count, $this->payouts_count, $this->intake_batches_count);

        return ['id' => $this->id, 'name' => $this->name, 'phone' => $this->phone, 'email' => $this->email, 'address' => $this->address, 'notes' => $this->notes, 'stock_status' => $this->stock_status, 'is_active' => $this->is_active, 'products_count' => $this->whenCounted('products'), 'stock_count' => $this->when(isset($this->stock_count), $this->stock_count), 'ready_count' => $this->when(isset($this->ready_count), $this->ready_count), 'draft_count' => $this->when(isset($this->draft_count), $this->draft_count), 'reserved_count' => $this->when(isset($this->reserved_count), $this->reserved_count), 'sold_count' => $this->when(isset($this->sold_count), $this->sold_count), 'payouts_count' => $this->whenCounted('payouts'), 'intake_batches_count' => $this->whenCounted('intakeBatches'), 'can_delete' => $this->when($hasDeletionCounts && isset($this->sold_count, $this->order_items_count), fn () => $this->sold_count === 0 && $this->payouts_count === 0 && $this->order_items_count === 0), 'products' => ProductResource::collection($this->whenLoaded('products')), 'created_at' => $this->created_at];
    }
}
