<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayoutRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage payouts') ?? false; }
    public function rules(): array { return ['consignor_id' => ['required', 'exists:consignors,id'], 'amount' => ['required', 'numeric', 'min:0'], 'status' => ['sometimes', Rule::in(['pending', 'paid'])], 'paid_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string']]; }
}
