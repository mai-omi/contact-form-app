<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertSame($category->id, $contact->category->id);
    }

    public function test_contact_belongs_to_many_tags(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $contact->tags()->attach($tags->pluck('id'));

        $this->assertCount(3, $contact->tags);
        $this->assertInstanceOf(Tag::class, $contact->tags->first());
    }
}
