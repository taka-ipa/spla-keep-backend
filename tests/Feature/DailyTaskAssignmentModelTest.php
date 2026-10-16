<?php

use App\Models\DailyTaskAssignment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;

it('does not allow two tasks in the same slot on the same day', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->for($user)->create();
    $taskB = Task::factory()->for($user)->create();

    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-16', 'slot' => 1, 'task_id' => $taskA->id]);

    expect(fn () => DailyTaskAssignment::factory()->for($user)->create([
        'date' => '2026-10-16', 'slot' => 1, 'task_id' => $taskB->id,
    ]))->toThrow(QueryException::class);
});

it('allows the same slot on different days', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-16', 'slot' => 1, 'task_id' => $task->id]);
    DailyTaskAssignment::factory()->for($user)->create(['date' => '2026-10-17', 'slot' => 1, 'task_id' => $task->id]);

    expect(DailyTaskAssignment::count())->toBe(2);
});

it('serializes the date as Y-m-d', function () {
    $assignment = DailyTaskAssignment::factory()->create(['date' => '2026-10-16']);

    expect($assignment->toArray()['date'])->toBe('2026-10-16');
});

it('is removed when its task is deleted', function () {
    $assignment = DailyTaskAssignment::factory()->create();

    $assignment->task->delete();

    expect(DailyTaskAssignment::count())->toBe(0);
});
