<?php

namespace Database\Factories;

use App\Models\ElectionRace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidate>
 */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'affiliation' => fake()->randomElement(['MDP', 'PNC']),
            'race_id' => ElectionRace::factory(),
        ];
    }
}
