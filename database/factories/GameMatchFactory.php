<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'played_at' => now(),
            'mode' => 'Xマッチ',
            'rule' => 'エリア',
            'stage' => 'ユノハナ大渓谷',
            'weapon' => 'わかばシューター',
            'is_win' => fake()->boolean(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
