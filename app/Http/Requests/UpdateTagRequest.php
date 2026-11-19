<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTagRequest extends TagRequest
{
    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('tags', 'name')->ignore($tag->id)],
        ];
    }
}
