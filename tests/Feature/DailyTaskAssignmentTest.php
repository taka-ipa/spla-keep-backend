<?php

use App\Models\DailyTaskAssignment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // UTC 2026-10-16 15:30 = JST 2026-10-17 00:30 → JSTでは翌日になっている時刻
    $this->travelTo(Carbon::parse('2026-10-16 15:30:00', 'UTC'));
});

it('returns both slots as null when nothing is assigned, defaulting to today in JST', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/daily-assignments');

    $response->assertStatus(200)
        ->assertJsonPath('date', '2026-10-17')
        ->assertJsonCount(2, 'assignments')
        ->assertJsonPath('assignments.0.slot', 1)
        ->assertJsonPath('assignments.0.task', null)
        ->assertJsonPath('assignments.1.slot', 2)
        ->assertJsonPath('assignments.1.task', null);
});

it('returns the assigned task and memo for the requested date', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'エイム練習']);
    DailyTaskAssignment::factory()->for($user)->create([
        'date' => '2026-10-10', 'slot' => 2, 'task_id' => $task->id, 'memo' => '30分',
    ]);

    $response = $this->actingAs($user)->getJson('/api/daily-assignments?date=2026-10-10');

    $response->assertStatus(200)
        ->assertJsonPath('date', '2026-10-10')
        ->assertJsonPath('assignments.0.task', null)
        ->assertJsonPath('assignments.1.task.id', $task->id)
        ->assertJsonPath('assignments.1.task.title', 'エイム練習')
        ->assertJsonPath('assignments.1.memo', '30分');
});

it('does not return other users\' assignments or other dates', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    DailyTaskAssignment::factory()->for($other)->create(['date' => '2026-10-10', 'slot' => 1]);
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-11', 'slot' => 1]);

    $response = $this->actingAs($user)->getJson('/api/daily-assignments?date=2026-10-10');

    $response->assertJsonPath('assignments.0.task', null)
        ->assertJsonPath('assignments.1.task', null);
});

it('rejects an invalid date format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/daily-assignments?date=10/10/2026')
        ->assertStatus(422)
        ->assertJsonValidationErrors('date');
});

it('requires authentication', function () {
    $this->getJson('/api/daily-assignments')->assertStatus(401);
});
