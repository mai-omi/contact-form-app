<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validPayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $categoryId,
            'detail' => 'お問い合わせ内容',
        ], $overrides);
    }

    public function test_passes_with_all_valid_fields(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id));

        $this->assertTrue($validator->passes());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'first_name' => ['first_name', '姓を入力してください'],
            'last_name' => ['last_name', '名を入力してください'],
            'email' => ['email', 'メールアドレスを入力してください'],
            'address' => ['address', '住所を入力してください'],
            'detail' => ['detail', 'お問い合わせ内容を入力してください'],
        ];
    }

    /**
     * @dataProvider requiredFieldProvider
     */
    public function test_fails_when_required_field_is_missing(string $field, string $expectedMessage): void
    {
        $category = Category::factory()->create();

        $data = $this->validPayload($category->id);
        unset($data[$field]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertSame($expectedMessage, $validator->errors()->first($field));
    }

    public function test_fails_when_gender_is_missing(): void
    {
        $category = Category::factory()->create();

        $data = $this->validPayload($category->id);
        unset($data['gender']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_fails_when_gender_is_not_in_one_two_three(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['gender' => 9]));

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_fails_when_category_id_is_missing(): void
    {
        $category = Category::factory()->create();

        $data = $this->validPayload($category->id);
        unset($data['category_id']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertSame('お問い合わせの種類を選択してください', $validator->errors()->first('category_id'));
    }

    public function test_fails_when_tel_has_too_few_digits(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['tel' => '123456789']));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    public function test_fails_when_tel_has_too_many_digits(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['tel' => '123456789012']));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    public function test_fails_when_tel_contains_non_digits(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['tel' => '090-1234-5678']));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    public function test_fails_when_category_id_does_not_exist(): void
    {
        $validator = $this->validate($this->validPayload(9999));

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたカテゴリが存在しません', $validator->errors()->first('category_id'));
    }

    public function test_fails_when_tag_id_does_not_exist(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['tag_ids' => [9999]]));

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたタグが存在しません', $validator->errors()->first('tag_ids.0'));
    }

    public function test_passes_when_tag_ids_exist(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $validator = $this->validate($this->validPayload($category->id, ['tag_ids' => [$tag->id]]));

        $this->assertTrue($validator->passes());
    }
}
