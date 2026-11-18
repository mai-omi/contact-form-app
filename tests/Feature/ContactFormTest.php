<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ], $overrides);
    }

    public function test_index_page_displays_categories_and_tags(): void
    {
        Category::factory()->count(5)->create();
        Tag::factory()->count(5)->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories', fn ($categories) => $categories->count() === 5);
        $response->assertViewHas('tags', fn ($tags) => $tags->count() === 5);
    }

    public function test_confirm_fails_when_first_name_is_missing(): void
    {
        $payload = $this->validPayload(['first_name' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['first_name' => '姓を入力してください']);
    }

    public function test_confirm_fails_when_last_name_is_missing(): void
    {
        $payload = $this->validPayload(['last_name' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['last_name' => '名を入力してください']);
    }

    public function test_confirm_fails_when_gender_is_missing(): void
    {
        $payload = $this->validPayload(['gender' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['gender' => '性別を選択してください']);
    }

    public function test_confirm_fails_when_gender_is_out_of_range(): void
    {
        $payload = $this->validPayload(['gender' => 9]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['gender' => '性別を選択してください']);
    }

    public function test_confirm_fails_with_japanese_message_when_gender_is_not_numeric(): void
    {
        $payload = $this->validPayload(['gender' => 'abc']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['gender' => '性別を選択してください']);
    }

    public function test_confirm_fails_when_email_is_missing(): void
    {
        $payload = $this->validPayload(['email' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_confirm_fails_when_email_format_is_invalid(): void
    {
        $payload = $this->validPayload(['email' => 'not-an-email']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_confirm_fails_with_japanese_message_when_email_exceeds_255_characters(): void
    {
        $payload = $this->validPayload(['email' => str_repeat('a', 250).'@a.com']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['email' => 'メールアドレスは255文字以内で入力してください']);
    }

    public function test_confirm_fails_when_tel_is_missing(): void
    {
        $payload = $this->validPayload(['tel' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['tel' => '電話番号を入力してください']);
    }

    public function test_confirm_fails_when_tel_contains_non_digits(): void
    {
        $payload = $this->validPayload(['tel' => '090-1234-5678']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['tel' => '電話番号を入力してください']);
    }

    public function test_confirm_fails_when_tel_has_too_few_digits(): void
    {
        $payload = $this->validPayload(['tel' => '123456789']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors('tel');
    }

    public function test_confirm_fails_when_tel_has_too_many_digits(): void
    {
        $payload = $this->validPayload(['tel' => '123456789012']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors('tel');
    }

    public function test_confirm_passes_when_tel_has_ten_or_eleven_digits(): void
    {
        $this->post('/contacts/confirm', $this->validPayload(['tel' => '0912345678']))->assertOk();
        $this->post('/contacts/confirm', $this->validPayload(['tel' => '09012345678']))->assertOk();
    }

    public function test_confirm_fails_when_address_is_missing(): void
    {
        $payload = $this->validPayload(['address' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['address' => '住所を入力してください']);
    }

    public function test_confirm_fails_with_japanese_message_when_address_exceeds_255_characters(): void
    {
        $payload = $this->validPayload(['address' => str_repeat('あ', 256)]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['address' => '住所は255文字以内で入力してください']);
    }

    public function test_confirm_passes_when_building_is_omitted(): void
    {
        $payload = $this->validPayload(['building' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertOk();
        $response->assertSessionDoesntHaveErrors('building');
    }

    public function test_confirm_fails_when_category_is_missing(): void
    {
        $payload = $this->validPayload(['category_id' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['category_id' => 'お問い合わせの種類を選択してください']);
    }

    public function test_confirm_fails_when_category_does_not_exist(): void
    {
        $payload = $this->validPayload(['category_id' => 999999]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['category_id' => 'お問い合わせの種類を選択してください']);
    }

    public function test_confirm_fails_with_japanese_message_when_category_is_not_numeric(): void
    {
        $payload = $this->validPayload(['category_id' => 'abc']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['category_id' => 'お問い合わせの種類を選択してください']);
    }

    public function test_confirm_fails_when_tag_does_not_exist(): void
    {
        $payload = $this->validPayload(['tag_ids' => [999999]]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors('tag_ids.0');
    }

    public function test_confirm_fails_when_detail_is_missing(): void
    {
        $payload = $this->validPayload(['detail' => '']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['detail' => 'お問い合わせ内容を入力してください']);
    }

    public function test_confirm_fails_when_detail_exceeds_120_characters(): void
    {
        $payload = $this->validPayload(['detail' => str_repeat('あ', 121)]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertSessionHasErrors(['detail' => 'お問い合わせ内容は120文字以内で入力してください']);
    }

    public function test_confirm_passes_when_detail_is_exactly_120_characters(): void
    {
        $payload = $this->validPayload(['detail' => str_repeat('あ', 120)]);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertOk();
    }

    public function test_confirm_displays_submitted_values_and_resolved_category(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);

        $response = $this->post('/contacts/confirm', $this->validPayload([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '花子',
            'email' => 'hanako@example.com',
            'detail' => 'テストのお問い合わせ内容',
        ]));

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田');
        $response->assertSee('花子');
        $response->assertSee('hanako@example.com');
        $response->assertSee('商品トラブル');
        $response->assertSee('テストのお問い合わせ内容');
        $response->assertViewHas('category', fn ($viewCategory) => $viewCategory->is($category));
    }

    public function test_confirm_displays_selected_tag_names(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->post('/contacts/confirm', $this->validPayload([
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $response->assertOk();
        $response->assertViewHas('tags', fn ($viewTags) => $viewTags->count() === 2);
        foreach ($tags as $tag) {
            $response->assertSee($tag->name);
        }
    }

    public function test_confirm_hides_tag_section_when_no_tags_selected(): void
    {
        $response = $this->post('/contacts/confirm', $this->validPayload());

        $response->assertOk();
        // confirm.blade.php has a plain (non-Blade) HTML comment "<!-- タグ -->"
        // that is always rendered, so assert on the visible label span instead.
        $response->assertDontSee('>タグ</span>', false);
    }
}
