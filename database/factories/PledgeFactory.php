<?php

namespace Database\Factories;

use App\Models\Pledge;
use App\Models\VoterRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pledge>
 */
class PledgeFactory extends Factory
{
    protected $model = Pledge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voter_id' => VoterRecord::factory(),
            'mayor' => fake()->randomElement(['MDP', 'PNC', 'DEM', null]),
            'raeesa' => fake()->randomElement(['MDP', 'PNC', 'DEM', null]),
            'council' => fake()->randomElement(['MDP', 'PNC', 'DEM', null]),
            'wdc' => fake()->randomElement(['MDP', 'PNC', 'DEM', null]),
        ];
    }
}
