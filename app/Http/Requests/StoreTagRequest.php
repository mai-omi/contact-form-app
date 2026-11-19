<?php

namespace App\Http\Requests;

class StoreTagRequest extends TagRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ];
    }
}
