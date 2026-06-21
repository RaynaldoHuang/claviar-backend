<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage sales') ?? false; }
    public function rules(): array { return ['product_id' => ['required', 'exists:products,id', Rule::unique('sales')->ignore($this->route('sale'))], 'customer_name' => ['required', 'string', 'max:255'], 'customer_phone' => ['required', 'string', 'min:8', 'max:30'], 'sale_price' => ['required', 'numeric', 'min:0'], 'payment_method' => ['required', 'string', 'max:50'], 'sold_at' => ['required', 'date']]; }
}
