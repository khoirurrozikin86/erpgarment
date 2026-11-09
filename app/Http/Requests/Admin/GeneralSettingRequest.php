<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decimal_places' => [
                'required',
                'integer',
                'min:0',
                'max:6',
            ],

            'negative_stock' => [
                'required',
                'boolean',
            ],

            'tax_included' => [
                'required',
                'boolean',
            ],
        ];
    }
}
