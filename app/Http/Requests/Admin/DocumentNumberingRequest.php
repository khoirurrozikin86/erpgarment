<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentNumberingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefix' => [
                'required',
                'string',
                'max:20',
            ],

            'format' => [
                'required',
                'string',
                'max:100',
            ],

            'next_number' => [
                'required',
                'integer',
                'min:1',
            ],

            'number_length' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'reset_period' => [
                'required',
                Rule::in([
                    'never',
                    'yearly',
                    'monthly',
                ]),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
