<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\MatchRating;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchRating>
 */
class MatchRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'task_id' => Task::factory(),
            'rating' => fake()->randomElement(MatchRating::RATINGS),
        ];
    }
}
