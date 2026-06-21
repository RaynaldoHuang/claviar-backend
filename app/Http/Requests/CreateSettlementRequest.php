<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSettlementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage payouts') ?? false; }
    public function rules(): array { return ['consignor_id' => ['required', 'exists:consignors,id'], 'notes' => ['nullable', 'string', 'max:2000']]; }
}
