<?php

use App\Models\Task;
use App\Models\User;

it('creates a match with ratings in one request', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->for($user)->create();
    $taskB = Task::factory()->for($user)->create();

    $response = $this->actingAs($user)->postJson('/api/matches-with-ratings', [
        'mode' => 'Xマッチ',
        'rule' => 'エリア',
        'stage' => 'ゴンズイ地区',
        'weapon' => 'スプラシューター',
        'is_win' => true,
        'note' => '序盤の対面が強かった',
        'ratings' => [
            ['task_id' => $taskA->id, 'rating' => '○'],
            ['task_id' => $taskB->id, 'rating' => '△'],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonCount(2, 'ratings')
        ->assertJsonPath('ratings.0.task.id', $taskA->id);

    $this->assertDatabaseHas('matches', ['user_id' => $user->id, 'stage' => 'ゴンズイ地区']);
    $this->assertDatabaseCount('match_ratings', 2);
});

it('rejects a rating for a task that belongs to another user', function () {
    $user = User::factory()->create();
    $otherUsersTask = Task::factory()->create();

    $this->actingAs($user)->postJson('/api/matches-with-ratings', [
        'mode' => 'Xマッチ', 'rule' => 'エリア', 'stage' => 'ゴンズイ地区', 'weapon' => 'スプラシューター',
        'ratings' => [['task_id' => $otherUsersTask->id, 'rating' => '○']],
    ])->assertStatus(422)->assertJsonValidationErrors('ratings.0.task_id');
});

it('rejects duplicate task_ids within the same request', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->postJson('/api/matches-with-ratings', [
        'mode' => 'Xマッチ', 'rule' => 'エリア', 'stage' => 'ゴンズイ地区', 'weapon' => 'スプラシューター',
        'ratings' => [
            ['task_id' => $task->id, 'rating' => '○'],
            ['task_id' => $task->id, 'rating' => '×'],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors('ratings.0.task_id');
});

it('rejects an invalid rating value', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->postJson('/api/matches-with-ratings', [
        'mode' => 'Xマッチ', 'rule' => 'エリア', 'stage' => 'ゴンズイ地区', 'weapon' => 'スプラシューター',
        'ratings' => [['task_id' => $task->id, 'rating' => 'すごく良い']],
    ])->assertStatus(422)->assertJsonValidationErrors('ratings.0.rating');
});

it('requires at least one rating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/matches-with-ratings', [
        'mode' => 'Xマッチ', 'rule' => 'エリア', 'stage' => 'ゴンズイ地区', 'weapon' => 'スプラシューター',
        'ratings' => [],
    ])->assertStatus(422)->assertJsonValidationErrors('ratings');
});
