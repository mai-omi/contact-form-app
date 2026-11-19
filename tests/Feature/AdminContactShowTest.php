<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactShowTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_show_displays_contact_details(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $tag = Tag::factory()->create(['name' => '質問']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'detail' => '商品が届きません',
        ]);
        $contact->tags()->attach($tag->id);

        $response = $this->get('/admin/contacts/'.$contact->id);

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('男性');
        $response->assertSee('yamada@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('東京都渋谷区1-1-1');
        $response->assertSee('商品トラブル');
        $response->assertSee('質問');
        $response->assertSee('商品が届きません');
    }

    public function test_show_loads_category_and_tags_relations(): void
    {
        $this->actingAsAdmin();
        $contact = Contact::factory()->create();

        $response = $this->get('/admin/contacts/'.$contact->id);

        $response->assertViewHas('contact', function ($contact) {
            return $contact->relationLoaded('category') && $contact->relationLoaded('tags');
        });
    }

    public function test_destroy_deletes_contact_and_redirects_to_admin(): void
    {
        $this->actingAsAdmin();
        $contact = Contact::factory()->create();

        $response = $this->delete('/admin/contacts/'.$contact->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_destroy_removes_contact_from_admin_list(): void
    {
        $this->actingAsAdmin();
        $contact = Contact::factory()->create();

        $this->delete('/admin/contacts/'.$contact->id);
        $response = $this->get('/admin');

        $response->assertViewHas('contacts', fn ($contacts) => ! $contacts->pluck('id')->contains($contact->id));
    }

    public function test_destroy_cascades_contact_tag_rows_via_http_route(): void
    {
        $this->actingAsAdmin();
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->delete('/admin/contacts/'.$contact->id);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_guest_viewing_contact_show_is_redirected_to_login(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->get('/admin/contacts/'.$contact->id);

        $response->assertRedirect('/login');
    }

    public function test_guest_deleting_contact_is_redirected_to_login(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->delete('/admin/contacts/'.$contact->id);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_show_returns_404_for_nonexistent_contact(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/contacts/999999');

        $response->assertNotFound();
    }

    public function test_destroy_returns_404_for_nonexistent_contact(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete('/admin/contacts/999999');

        $response->assertNotFound();
    }
}
