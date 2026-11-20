<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_contacts_with_data_and_meta(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.total', 3);
        $this->assertArrayHasKey('last_page', $response->json('meta'));
    }

    public function test_index_filters_by_keyword(): void
    {
        $match = Contact::factory()->create(['first_name' => '田中']);
        Contact::factory()->create(['first_name' => '鈴木']);

        $response = $this->getJson('/api/v1/contacts?keyword=田中');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_filters_by_gender(): void
    {
        $match = Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->getJson('/api/v1/contacts?gender=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_filters_by_category_id(): void
    {
        $category = Category::factory()->create();
        $match = Contact::factory()->create(['category_id' => $category->id]);
        Contact::factory()->create();

        $response = $this->getJson('/api/v1/contacts?category_id='.$category->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_paginates_with_per_page(): void
    {
        Contact::factory()->count(6)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=5');

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_index_returns_422_when_gender_is_invalid(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=9');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
    }

    public function test_index_returns_422_when_category_id_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/contacts?category_id=9999');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリが存在しません');
    }

    public function test_index_returns_json_error_even_without_accept_header(): void
    {
        $response = $this->get('/api/v1/contacts?gender=9');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
    }

    public function test_show_returns_contact_with_category_and_tags(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);
        $contact->tags()->attach($tag->id);

        $response = $this->getJson('/api/v1/contacts/'.$contact->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.category.id', $category->id);
        $response->assertJsonPath('data.tags.0.id', $tag->id);
    }

    public function test_show_returns_404_when_contact_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    public function test_store_creates_contact_and_returns_201(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', '太郎');
        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
        ]);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail']);
    }

    public function test_store_returns_422_when_tel_format_is_invalid(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/contacts', $this->validStorePayload($category->id, ['tel' => '123456789']));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください');
    }

    public function test_store_returns_422_when_category_id_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/contacts', $this->validStorePayload(9999));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリが存在しません');
    }

    public function test_store_returns_422_when_tag_id_does_not_exist(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/contacts', $this->validStorePayload($category->id, ['tag_ids' => [9999]]));

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertSame('選択されたタグが存在しません', $errors['tag_ids.0'][0]);
    }

    public function test_store_attaches_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/contacts', $this->validStorePayload($category->id, [
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $response->assertCreated();
        $contactId = $response->json('data.id');
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', ['contact_id' => $contactId, 'tag_id' => $tag->id]);
        }
    }

    public function test_update_updates_contact_and_returns_200(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $this->validStorePayload($category->id, [
            'first_name' => '更新後の姓',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.first_name', '更新後の姓');
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => '更新後の姓']);
    }

    public function test_update_syncs_tags_replacing_previous_ones(): void
    {
        $category = Category::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);
        $contact->tags()->attach($oldTag->id);

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $this->validStorePayload($category->id, [
            'tag_ids' => [$newTag->id],
        ]));

        $response->assertOk();
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $oldTag->id]);
        $this->assertDatabaseHas('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $newTag->id]);
    }

    public function test_update_returns_404_when_contact_does_not_exist(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson('/api/v1/contacts/999999', $this->validStorePayload($category->id));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    public function test_destroy_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson('/api/v1/contacts/'.$contact->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_destroy_cascades_delete_of_contact_tag_rows(): void
    {
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->deleteJson('/api/v1/contacts/'.$contact->id)->assertStatus(204);

        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_destroy_returns_404_when_contact_does_not_exist(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    private function validStorePayload(int $categoryId, array $overrides = []): array
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
}
