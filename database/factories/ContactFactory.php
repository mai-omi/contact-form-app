<?php

namespace Database\Factories;

use App\Models\Category;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    protected static ?\Faker\Generator $jaFaker = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = static::$jaFaker ??= FakerFactory::create('ja_JP');

        return [
            'category_id' => Category::factory(),
            'first_name' => $faker->lastName(),
            'last_name' => $faker->firstName(),
            'gender' => $faker->numberBetween(1, 3),
            'email' => $faker->unique()->safeEmail(),
            'tel' => $faker->numerify(str_repeat('#', $faker->randomElement([10, 11]))),
            'address' => $faker->address(),
            'building' => $faker->optional()->secondaryAddress(),
            'detail' => $faker->realText(100),
        ];
    }
}
