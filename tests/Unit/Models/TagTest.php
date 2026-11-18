<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts(): void
    {
        $tag = Tag::create(['name' => '質問']);
        $contacts = Contact::factory()->count(2)->create();

        $tag->contacts()->attach($contacts->pluck('id'));

        $this->assertCount(2, $tag->contacts);
        $this->assertInstanceOf(Contact::class, $tag->contacts->first());
    }
}
