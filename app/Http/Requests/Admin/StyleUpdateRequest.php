<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StyleUpdateRequest extends StyleStoreRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', Rule::unique('styles', 'code')->ignore($this->route('style')?->id)],
            'name' => ['required', 'string', 'max:150'],
        ];
    }
}
