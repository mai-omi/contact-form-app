<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('ja_JP');
        $categoryIds = Category::pluck('id');
        $tagIds = Tag::pluck('id');

        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => $faker->randomElement($categoryIds),
                'first_name' => $faker->lastName(),
                'last_name' => $faker->firstName(),
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail(),
                'tel' => $faker->numerify(str_repeat('#', $faker->randomElement([10, 11]))),
                'address' => $faker->address(),
                'building' => $faker->optional()->secondaryAddress(),
                'detail' => $faker->realText(100),
            ]);

            $contact->tags()->attach(
                $tagIds->random($faker->numberBetween(1, 3))
            );
        }
    }
}
