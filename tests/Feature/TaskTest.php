<?php

use App\Models\Task;
use App\Models\User;

it('lists only the authenticated user\'s tasks, ordered by sort_order', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Task::factory()->for($user)->create(['title' => 'B', 'sort_order' => 1]);
    Task::factory()->for($user)->create(['title' => 'A', 'sort_order' => 0]);
    Task::factory()->for($other)->create(['title' => '他人のタスク']);

    $response = $this->actingAs($user)->getJson('/api/tasks');

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(2);
    expect($response->json('0.title'))->toBe('A');
});

it('creates a task for the authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tasks', [
        'title' => 'エイム練習',
        'description' => '毎日10分',
    ]);

    $response->assertStatus(201)->assertJson([
        'title' => 'エイム練習',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'エイム練習',
    ]);
});

it('updates a task the user owns (the previously broken edit feature)', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => '旧タイトル']);

    $response = $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
        'title' => '新タイトル',
        'is_active' => false,
    ]);

    $response->assertStatus(200)->assertJson([
        'title' => '新タイトル',
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => '新タイトル',
        'is_active' => false,
    ]);
});

it('does not allow updating another user\'s task', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    $this->actingAs($attacker)
        ->patchJson("/api/tasks/{$task->id}", ['title' => '乗っ取り'])
        ->assertStatus(404);
});

it('deletes a task the user owns', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")->assertStatus(204);

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

it('does not allow deleting another user\'s task', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    $this->actingAs($attacker)
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertStatus(404);

    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});
