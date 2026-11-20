<?php

namespace Database\Factories;

use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<OtpChallenge>
 */
class OtpChallengeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'purpose' => 'email_change',
            'channel' => 'email',
            'recipient' => fake()->safeEmail(),
            'requested_value' => fake()->safeEmail(),
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
        ];
    }
}
