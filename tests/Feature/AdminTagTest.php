<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_store_creates_tag_and_redirects_to_admin(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/admin/tags', ['name' => 'ユニークな名前']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => 'ユニークな名前']);
    }

    public function test_store_adds_new_tag_to_admin_list(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/tags', ['name' => 'ユニークな名前']);
        $response = $this->get('/admin');

        $response->assertViewHas('tags', fn ($tags) => $tags->pluck('name')->contains('ユニークな名前'));
    }

    public function test_store_fails_when_name_is_empty(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/admin/tags', ['name' => '']);

        $response->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
    }

    public function test_store_fails_when_name_exceeds_fifty_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/admin/tags', ['name' => str_repeat('あ', 51)]);

        $response->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);
    }

    public function test_store_fails_when_name_is_duplicate(): void
    {
        $this->actingAsAdmin();
        Tag::factory()->create(['name' => 'ユニークな名前']);

        $response = $this->post('/admin/tags', ['name' => 'ユニークな名前']);

        $response->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
    }

    public function test_edit_displays_form_with_current_tag_name(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => 'ユニークな名前']);

        $response = $this->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertOk();
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('ユニークな名前');
    }

    public function test_edit_returns_404_for_nonexistent_tag(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/tags/999999/edit');

        $response->assertNotFound();
    }

    public function test_update_changes_tag_name_and_redirects_to_admin(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => '元の名前']);

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => '新しい名前']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '新しい名前']);
    }

    public function test_update_reflects_new_name_in_admin_list(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => '元の名前']);

        $this->put('/admin/tags/'.$tag->id, ['name' => '新しい名前']);
        $response = $this->get('/admin');

        $response->assertViewHas('tags', fn ($tags) => $tags->pluck('name')->contains('新しい名前'));
    }

    public function test_update_with_unchanged_name_does_not_error(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => '元の名前']);

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => '元の名前']);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin');
    }

    public function test_update_fails_when_name_is_empty(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create();

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => '']);

        $response->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
    }

    public function test_update_fails_when_name_exceeds_fifty_characters(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create();

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => str_repeat('あ', 51)]);

        $response->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);
    }

    public function test_update_fails_when_name_duplicates_another_tag(): void
    {
        $this->actingAsAdmin();
        Tag::factory()->create(['name' => '別のタグ']);
        $tag = Tag::factory()->create(['name' => '元の名前']);

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => '別のタグ']);

        $response->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
    }

    public function test_destroy_deletes_tag_and_redirects_to_admin(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create();

        $response = $this->delete('/admin/tags/'.$tag->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_destroy_removes_tag_from_admin_list(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => 'ユニークな名前']);

        $this->delete('/admin/tags/'.$tag->id);
        $response = $this->get('/admin');

        $response->assertViewHas('tags', fn ($tags) => ! $tags->pluck('id')->contains($tag->id));
    }

    public function test_destroy_returns_404_for_nonexistent_tag(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete('/admin/tags/999999');

        $response->assertNotFound();
    }

    public function test_guest_editing_tag_page_is_redirected_to_login(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertRedirect('/login');
    }

    public function test_guest_storing_tag_is_redirected_to_login(): void
    {
        $response = $this->post('/admin/tags', ['name' => 'ユニークな名前']);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('tags', ['name' => 'ユニークな名前']);
    }

    public function test_guest_updating_tag_is_redirected_to_login(): void
    {
        $tag = Tag::factory()->create(['name' => '元の名前']);

        $response = $this->put('/admin/tags/'.$tag->id, ['name' => '新しい名前']);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '元の名前']);
    }

    public function test_guest_deleting_tag_is_redirected_to_login(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->delete('/admin/tags/'.$tag->id);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
