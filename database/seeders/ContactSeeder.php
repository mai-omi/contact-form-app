<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $tagIds = Tag::pluck('id');

        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::factory()->create([
                'category_id' => $categoryIds->random(),
            ]);

            $contact->tags()->attach(
                $tagIds->random(random_int(1, 3))
            );
        }
    }
}
