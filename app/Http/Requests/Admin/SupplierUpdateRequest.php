<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SupplierUpdateRequest extends SupplierStoreRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($this->route('supplier')?->id)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
