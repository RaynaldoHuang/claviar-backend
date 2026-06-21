<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsignorRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage consignors') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]; }
}
