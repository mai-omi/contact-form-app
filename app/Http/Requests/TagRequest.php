<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'タグ名を入力してください',
            'name.string' => 'タグ名は文字列で入力してください',
            'name.max' => 'タグ名は50文字以内で入力してください',
            'name.unique' => 'そのタグ名は既に使用されています',
        ];
    }
}
