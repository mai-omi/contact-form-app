<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => fake()->lastName(),
            'last_name' => fake()->firstName(),
            'gender' => fake()->numberBetween(1, 3),
            'email' => fake()->unique()->safeEmail(),
            'tel' => fake()->numerify(str_repeat('#', fake()->randomElement([10, 11]))),
            'address' => fake()->address(),
            'building' => fake()->optional()->secondaryAddress(),
            'detail' => fake()->realText(100),
        ];
    }
}
