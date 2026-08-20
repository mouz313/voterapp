<?php

namespace Database\Factories;

use App\Models\Voter;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoterFactory extends Factory
{
    protected $model = Voter::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'father_name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#######-#######-#'),
            'silsala_no' => (string) fake()->numberBetween(1, 50),
            'gharana_no' => 'G-' . fake()->numberBetween(100, 999),
        ];
    }
}
