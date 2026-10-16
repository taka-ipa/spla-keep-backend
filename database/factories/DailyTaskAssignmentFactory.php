<?php

namespace Database\Factories;

use App\Models\DailyTaskAssignment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyTaskAssignment>
 */
class DailyTaskAssignmentFactory extends Factory
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
            'date' => now()->toDateString(),
            'slot' => 1,
            'task_id' => Task::factory(),
            'memo' => null,
        ];
    }
}
