<?php

namespace Database\Factories;

use App\Models\VoterRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VoterRecord>
 */
class VoterRecordFactory extends Factory
{
    protected $model = VoterRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'list_number' => fake()->unique()->numberBetween(1, 100000),
            'id_card_number' => strtoupper(fake()->bothify('A######')),
            'agent' => fake()->optional()->name(),
            'photo_path' => null,
            'name' => fake()->name(),
            'sex' => fake()->randomElement(['M', 'F']),
            'mobile' => fake()->numerify('9#######'),
            'dob' => fake()->date(),
            'age' => fake()->numberBetween(18, 90),
            'registered_box' => fake()->city(),
            'majilis_con' => fake()->randomElement(['North', 'South', 'Central']),
            'address' => fake()->streetAddress(),
            'dhaairaa' => fake()->randomElement(['Dhaairaa A', 'Dhaairaa B', 'Dhaairaa C']),
            're_reg_travel' => fake()->optional()->word(),
            'comments' => fake()->optional()->sentence(),
            'vote_status' => fake()->randomElement(['Voted', 'Not Voted']),
        ];
    }
}
