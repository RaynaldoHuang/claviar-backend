<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage products') ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('products')->ignore($product)],
            'consignor_id' => ['required', 'exists:consignors,id'], 'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'], 'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'gte:purchase_price'], 'condition' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['available', 'reserved', 'sold', 'returned'])],
            'images' => ['sometimes', 'array', 'max:10'], 'images.*' => ['image', 'max:10240'], 'cover_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
