<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void { $phone = preg_replace('/[^0-9+]/', '', (string) $this->input('phone')); $this->merge(['phone' => $phone ?: null]); }
    public function authorize(): bool { return $this->user()?->can('manage sales') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'min:8', 'max:30', Rule::unique('customers', 'phone')->ignore($this->route('customer'))], 'notes' => ['nullable', 'string', 'max:2000']]; }
}
