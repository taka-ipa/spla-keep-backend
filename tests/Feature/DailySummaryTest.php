<?php

use App\Models\GameMatch;
use App\Models\MatchRating;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // UTC 2026-10-13 03:00 = JST 2026-10-13 12:00
    $this->travelTo(Carbon::parse('2026-10-13 03:00:00', 'UTC'));
});

it('returns 7 zero-filled days ending today in JST', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    $response->assertStatus(200)
        ->assertJsonPath('range.from', '2026-10-07')
        ->assertJsonPath('range.to', '2026-10-13')
        ->assertJsonCount(7, 'days')
        ->assertJsonPath('days.0.date', '2026-10-07')
        ->assertJsonPath('days.0.matches', 0)
        ->assertJsonPath('days.0.win_rate', null)
        ->assertJsonPath('days.6.date', '2026-10-13');
});

it('counts wins, losses and win rate per day, ignoring matches without a result', function () {
    $user = User::factory()->create();
    $today = now();

    GameMatch::factory()->for($user)->create(['is_win' => true, 'created_at' => $today]);
    GameMatch::factory()->for($user)->create(['is_win' => true, 'created_at' => $today]);
    GameMatch::factory()->for($user)->create(['is_win' => false, 'created_at' => $today]);
    GameMatch::factory()->for($user)->create(['is_win' => null, 'created_at' => $today]);

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    $response->assertJsonPath('days.6.matches', 4)
        ->assertJsonPath('days.6.wins', 2)
        ->assertJsonPath('days.6.losses', 1)
        ->assertJsonPath('days.6.win_rate', 66.7);
});

it('returns totals across the whole 7-day window', function () {
    $user = User::factory()->create();

    GameMatch::factory()->for($user)->create(['is_win' => true, 'created_at' => now()]);
    GameMatch::factory()->for($user)->create(['is_win' => true, 'created_at' => now()->subDays(3)]);
    GameMatch::factory()->for($user)->create(['is_win' => false, 'created_at' => now()->subDays(5)]);

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    $response->assertJsonPath('totals.matches', 3)
        ->assertJsonPath('totals.wins', 2)
        ->assertJsonPath('totals.losses', 1)
        ->assertJsonPath('totals.win_rate', 66.7);
});

it('counts ratings per day and in totals, excluding "-"', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->for($user)->create();
    $taskB = Task::factory()->for($user)->create();

    $today = GameMatch::factory()->for($user)->create(['created_at' => now()]);
    MatchRating::factory()->for($today, 'match')->create(['task_id' => $taskA->id, 'rating' => '◎']);
    MatchRating::factory()->for($today, 'match')->create(['task_id' => $taskB->id, 'rating' => '-']);

    $earlier = GameMatch::factory()->for($user)->create(['created_at' => now()->subDays(2)]);
    MatchRating::factory()->for($earlier, 'match')->create(['task_id' => $taskA->id, 'rating' => '◎']);
    MatchRating::factory()->for($earlier, 'match')->create(['task_id' => $taskB->id, 'rating' => '×']);

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    expect($response->json('days.6.ratings'))->toBe(['◎' => 1, '○' => 0, '△' => 0, '×' => 0]);
    expect($response->json('days.4.ratings'))->toBe(['◎' => 1, '○' => 0, '△' => 0, '×' => 1]);
    expect($response->json('totals.ratings'))->toBe(['◎' => 2, '○' => 0, '△' => 0, '×' => 1]);
});

it('groups matches by JST date, not UTC date', function () {
    $user = User::factory()->create();

    // UTC 2026-10-12 16:00 = JST 2026-10-13 01:00 → JSTでは「今日」
    GameMatch::factory()->for($user)->create([
        'is_win' => true,
        'created_at' => Carbon::parse('2026-10-12 16:00:00', 'UTC'),
    ]);

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    $response->assertJsonPath('days.6.date', '2026-10-13')
        ->assertJsonPath('days.6.matches', 1)
        ->assertJsonPath('days.5.matches', 0);
});

it('excludes matches outside the 7-day window and other users\' matches', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    GameMatch::factory()->for($user)->create(['created_at' => now()->subDays(10)]);
    GameMatch::factory()->for($other)->create(['created_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/daily-summary');

    $total = collect($response->json('days'))->sum('matches');
    expect($total)->toBe(0);
});

it('requires authentication', function () {
    $this->getJson('/api/daily-summary')->assertStatus(401);
});
