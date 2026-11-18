<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_contacts(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        Contact::factory()->count(2)->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }
}
