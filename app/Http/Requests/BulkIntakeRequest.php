<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkIntakeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage products') ?? false; }
    public function rules(): array { return ['quantity' => ['required', 'integer', 'min:1', 'max:500'], 'notes' => ['nullable', 'string', 'max:2000']]; }
}
