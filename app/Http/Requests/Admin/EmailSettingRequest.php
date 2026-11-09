<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mail_mailer' => [
                'required',
                'string',
                'max:50',
            ],

            'mail_host' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mail_port' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'mail_username' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mail_password' => [
                'nullable',
                'string',
                'max:500',
            ],

            'mail_encryption' => [
                'nullable',
                'in:tls,ssl',
            ],

            'from_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'from_email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
