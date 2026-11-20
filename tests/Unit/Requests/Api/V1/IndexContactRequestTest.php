<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_passes_when_all_fields_are_omitted(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_keyword_exceeds_255_characters(): void
    {
        $validator = $this->validate(['keyword' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertSame('キーワードは255文字以内で入力してください', $validator->errors()->first('keyword'));
    }

    public function test_fails_when_gender_is_not_in_one_two_three(): void
    {
        $validator = $this->validate(['gender' => 9]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_fails_when_gender_is_zero(): void
    {
        $validator = $this->validate(['gender' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_fails_when_category_id_does_not_exist(): void
    {
        $validator = $this->validate(['category_id' => 9999]);

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたカテゴリが存在しません', $validator->errors()->first('category_id'));
    }

    public function test_passes_when_category_id_exists(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate(['category_id' => $category->id]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_date_format_is_invalid(): void
    {
        $validator = $this->validate(['date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertSame('日付の形式が正しくありません', $validator->errors()->first('date'));
    }

    public function test_fails_when_per_page_is_zero(): void
    {
        $validator = $this->validate(['per_page' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertSame('1ページあたりの件数は1〜100の範囲で指定してください', $validator->errors()->first('per_page'));
    }

    public function test_fails_when_per_page_exceeds_100(): void
    {
        $validator = $this->validate(['per_page' => 101]);

        $this->assertTrue($validator->fails());
        $this->assertSame('1ページあたりの件数は1〜100の範囲で指定してください', $validator->errors()->first('per_page'));
    }

    public function test_passes_when_per_page_is_100(): void
    {
        $validator = $this->validate(['per_page' => 100]);

        $this->assertTrue($validator->passes());
    }
}
