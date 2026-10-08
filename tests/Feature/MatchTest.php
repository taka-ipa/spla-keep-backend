<?php

use App\Models\GameMatch;
use App\Models\MatchRating;
use App\Models\Task;
use App\Models\User;

it('lists only the authenticated user\'s matches with pagination', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    GameMatch::factory()->for($user)->count(3)->create();
    GameMatch::factory()->for($other)->create();

    $response = $this->actingAs($user)->getJson('/api/matches?per_page=2');

    $response->assertStatus(200);
    expect($response->json('total'))->toBe(3);
    expect($response->json('data'))->toHaveCount(2);
});

it('filters matches by stage and weapon', function () {
    $user = User::factory()->create();

    GameMatch::factory()->for($user)->create(['stage' => 'ゴンズイ地区', 'weapon' => 'わかばシューター']);
    GameMatch::factory()->for($user)->create(['stage' => 'ユノハナ大渓谷', 'weapon' => 'スプラシューター']);

    $response = $this->actingAs($user)->getJson('/api/matches?'.http_build_query(['stage' => 'ゴンズイ地区']));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.stage'))->toBe('ゴンズイ地区');
});

it('shows a match with its ratings', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'エイム練習']);
    $match = GameMatch::factory()->for($user)->create();
    MatchRating::factory()->for($match, 'match')->create(['task_id' => $task->id, 'rating' => '○']);

    $response = $this->actingAs($user)->getJson("/api/matches/{$match->id}");

    $response->assertStatus(200)
        ->assertJsonPath('match.id', $match->id)
        ->assertJsonPath('ratings.0.title', 'エイム練習')
        ->assertJsonPath('ratings.0.rating', '○');
});

it('does not allow viewing another user\'s match', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $match = GameMatch::factory()->for($owner)->create();

    $this->actingAs($attacker)->getJson("/api/matches/{$match->id}")->assertStatus(404);
});

it('updates match fields without touching ratings when ratings are omitted', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $match = GameMatch::factory()->for($user)->create(['note' => '旧メモ']);
    MatchRating::factory()->for($match, 'match')->create(['task_id' => $task->id, 'rating' => '○']);

    $response = $this->actingAs($user)->patchJson("/api/matches/{$match->id}", [
        'note' => '新メモ',
    ]);

    $response->assertStatus(200)->assertJsonPath('note', '新メモ');
    expect($match->fresh()->ratings)->toHaveCount(1);
});

it('replaces all ratings when ratings are sent', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->for($user)->create();
    $taskB = Task::factory()->for($user)->create();
    $match = GameMatch::factory()->for($user)->create();
    MatchRating::factory()->for($match, 'match')->create(['task_id' => $taskA->id, 'rating' => '×']);

    $response = $this->actingAs($user)->patchJson("/api/matches/{$match->id}", [
        'ratings' => [
            ['task_id' => $taskB->id, 'rating' => '◎'],
        ],
    ]);

    $response->assertStatus(200);
    $ratings = $match->fresh()->ratings;
    expect($ratings)->toHaveCount(1);
    expect($ratings->first()->task_id)->toBe($taskB->id);
    expect($ratings->first()->rating)->toBe('◎');
});

it('does not allow updating another user\'s match', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $match = GameMatch::factory()->for($owner)->create();

    $this->actingAs($attacker)
        ->patchJson("/api/matches/{$match->id}", ['note' => '乗っ取り'])
        ->assertStatus(404);
});
