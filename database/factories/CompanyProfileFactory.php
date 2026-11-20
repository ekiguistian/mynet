<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_key' => fake()->unique()->slug(2),
            'company_name' => fake()->company(),
            'owner_name' => fake()->name(),
            'owner_phone' => fake()->phoneNumber(),
            'responsible_name' => fake()->name(),
            'responsible_phone' => fake()->phoneNumber(),
            'noc_phone' => fake()->phoneNumber(),
            'cs_phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'social_media' => [],
        ];
    }
}
