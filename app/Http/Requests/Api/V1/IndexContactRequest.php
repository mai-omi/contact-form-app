<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'gender.integer' => '性別の値が不正です',
            'gender.in' => '性別の値が不正です',
            'category_id.integer' => '選択されたカテゴリが存在しません',
            'category_id.exists' => '選択されたカテゴリが存在しません',
            'date.date' => '日付の形式が正しくありません',
            'per_page.integer' => '1ページあたりの件数は1〜100の範囲で指定してください',
            'per_page.min' => '1ページあたりの件数は1〜100の範囲で指定してください',
            'per_page.max' => '1ページあたりの件数は1〜100の範囲で指定してください',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
    }
}
