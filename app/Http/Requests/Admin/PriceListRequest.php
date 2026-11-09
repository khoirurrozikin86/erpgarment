<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST') ? 'pricelists.create' : 'pricelists.update';
        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        $priceList = $this->route('priceList');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('price_lists', 'code')->ignore($priceList)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['sales', 'purchase'])],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
