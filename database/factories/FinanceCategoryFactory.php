<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => null,
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(['income', 'expense', 'both']),
            'active' => true,
        ];
    }
}
