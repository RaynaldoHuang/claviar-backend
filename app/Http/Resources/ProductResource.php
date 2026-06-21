<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'purchase_price' => $this->purchase_price === null ? null : (float) $this->purchase_price, 'selling_price' => $this->selling_price === null ? null : (float) $this->selling_price, 'condition' => $this->condition, 'status' => $this->status === 'reserved' ? 'pending' : $this->status, 'is_draft' => $this->is_draft, 'intake_batch_id' => $this->intake_batch_id, 'consignor' => new ConsignorResource($this->whenLoaded('consignor')), 'category' => $this->whenLoaded('category'), 'brand' => $this->whenLoaded('brand'), 'customer' => $this->whenLoaded('sale', fn () => $this->sale?->customer ? ['id' => $this->sale->customer->id, 'name' => $this->sale->customer->name, 'phone' => $this->sale->customer->phone] : null), 'sold_at' => $this->whenLoaded('sale', fn () => $this->sale?->sold_at), 'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => ['id' => $image->id, 'url' => $request->getSchemeAndHttpHost().Storage::url($image->image), 'is_cover' => $image->is_cover, 'sort_order' => $image->sort_order])), 'created_at' => $this->created_at]; }
}
