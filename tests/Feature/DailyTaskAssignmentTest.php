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

it('assigns a task to a slot and returns 201', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'エイム練習']);

    $response = $this->actingAs($user)->putJson('/api/daily-assignments/1', [
        'date' => '2026-10-10',
        'task_id' => $task->id,
        'memo' => '30分',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('date', '2026-10-10')
        ->assertJsonPath('slot', 1)
        ->assertJsonPath('task.title', 'エイム練習')
        ->assertJsonPath('memo', '30分');

    expect(DailyTaskAssignment::count())->toBe(1);
});

it('defaults the date to today in JST when omitted', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->putJson('/api/daily-assignments/2', ['task_id' => $task->id])
        ->assertStatus(201)
        ->assertJsonPath('date', '2026-10-17');
});

it('replaces the task in an already-filled slot and returns 200', function () {
    $user = User::factory()->create();
    $old = Task::factory()->for($user)->create();
    $new = Task::factory()->for($user)->create(['title' => '新しい課題']);
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-10', 'slot' => 1, 'task_id' => $old->id]);

    $response = $this->actingAs($user)->putJson('/api/daily-assignments/1', [
        'date' => '2026-10-10',
        'task_id' => $new->id,
    ]);

    $response->assertStatus(200)->assertJsonPath('task.title', '新しい課題');
    expect(DailyTaskAssignment::count())->toBe(1);
});

it('rejects the same task in both slots on the same day', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-10', 'slot' => 1, 'task_id' => $task->id]);

    $this->actingAs($user)->putJson('/api/daily-assignments/2', [
        'date' => '2026-10-10',
        'task_id' => $task->id,
    ])->assertStatus(422)->assertJsonValidationErrors('task_id');
});

it('allows the same task in the same slot on different days', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-10', 'slot' => 1, 'task_id' => $task->id]);

    $this->actingAs($user)->putJson('/api/daily-assignments/1', [
        'date' => '2026-10-11',
        'task_id' => $task->id,
    ])->assertStatus(201);
});

it('rejects another user\'s task and inactive tasks', function () {
    $user = User::factory()->create();
    $othersTask = Task::factory()->create();
    $inactive = Task::factory()->for($user)->create(['is_active' => false]);

    $this->actingAs($user)->putJson('/api/daily-assignments/1', ['task_id' => $othersTask->id])
        ->assertStatus(422)->assertJsonValidationErrors('task_id');
    $this->actingAs($user)->putJson('/api/daily-assignments/1', ['task_id' => $inactive->id])
        ->assertStatus(422)->assertJsonValidationErrors('task_id');
});

it('returns 404 for a slot other than 1 or 2', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->putJson('/api/daily-assignments/3', ['task_id' => $task->id])
        ->assertStatus(404);
});

it('clears a slot and returns 204', function () {
    $user = User::factory()->create();
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-10', 'slot' => 1]);
    $keep = DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-10', 'slot' => 2]);

    $this->actingAs($user)->deleteJson('/api/daily-assignments/1?date=2026-10-10')->assertStatus(204);

    expect(DailyTaskAssignment::count())->toBe(1);
    expect(DailyTaskAssignment::first()->id)->toBe($keep->id);
});

it('defaults to today in JST when clearing without a date', function () {
    $user = User::factory()->create();
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-17', 'slot' => 1]);
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-16', 'slot' => 1]);

    $this->actingAs($user)->deleteJson('/api/daily-assignments/1')->assertStatus(204);

    expect(DailyTaskAssignment::count())->toBe(1);
    expect(DailyTaskAssignment::first()->date->toDateString())->toBe('2026-10-16');
});

it('returns 204 even when the slot is already empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->deleteJson('/api/daily-assignments/1?date=2026-10-10')->assertStatus(204);
});

it('does not clear another user\'s assignment', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    DailyTaskAssignment::factory()->for($owner)->create(['date' => '2026-10-10', 'slot' => 1]);

    $this->actingAs($attacker)->deleteJson('/api/daily-assignments/1?date=2026-10-10')->assertStatus(204);

    expect(DailyTaskAssignment::count())->toBe(1);
});

it('returns 404 when clearing a slot other than 1 or 2', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->deleteJson('/api/daily-assignments/3')->assertStatus(404);
});
