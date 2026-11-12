<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImporterStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tax_number' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'apip' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
