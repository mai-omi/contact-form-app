<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_category_cascades_to_contacts(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $category->delete();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_deleting_contact_cascades_to_contact_tag(): void
    {
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $contact->delete();

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_deleting_tag_cascades_to_contact_tag(): void
    {
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $tag->delete();

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_deleting_tag_via_admin_route_cascades_to_contact_tag(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->actingAs($user)->delete('/admin/tags/'.$tag->id);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_tag_combination_must_be_unique(): void
    {
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->expectException(QueryException::class);

        $contact->tags()->attach($tag->id);
    }
}
