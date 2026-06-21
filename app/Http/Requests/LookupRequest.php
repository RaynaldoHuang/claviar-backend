<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage products') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255']]; }
}
