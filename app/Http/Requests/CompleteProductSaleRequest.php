<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProductSaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage sales') ?? false; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'], 'condition' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'], 'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'gte:purchase_price'],
            'customer_id' => ['required', 'exists:customers,id'], 'payment_method' => ['required', 'string', 'max:50'],
            'sold_at' => ['nullable', 'date'], 'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'max:10240'], 'cover_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
