<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ElectionRace>
 */
class ElectionRaceFactory extends Factory
{
    public function definition(): array
    {
        $dhaaira = fake()->randomElement(['B9-1', 'B9-2', 'B9-3', 'B9-4', 'B9-5', 'B9-6', null]);
        $type = $dhaaira !== null
            ? fake()->randomElement(['council', 'wdc'])
            : fake()->randomElement(['mayor', 'raeesa']);

        return [
            'name' => fake()->words(3, true),
            'dhaaira' => $dhaaira,
            'type' => $type,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
