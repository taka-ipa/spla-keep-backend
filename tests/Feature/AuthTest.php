<?php

use App\Models\User;

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'さけべろ',
        'login_id' => 'sakebero',
        'email' => 'sakebero@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'login_id', 'email']]);

    $this->assertDatabaseHas('users', [
        'login_id' => 'sakebero',
        'email' => 'sakebero@example.com',
    ]);
});

it('rejects registration when login_id is already taken', function () {
    User::factory()->create(['login_id' => 'sakebero']);

    $response = $this->postJson('/api/register', [
        'name' => 'テスト',
        'login_id' => 'sakebero',
        'email' => 'other@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('login_id');
});

it('logs in with correct login_id and password', function () {
    User::factory()->create([
        'login_id' => 'sakebero',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'login_id' => 'sakebero',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'login_id', 'email']]);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'login_id' => 'sakebero',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'login_id' => 'sakebero',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

it('returns the authenticated user via /api/user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/user');

    $response->assertStatus(200)->assertJson([
        'id' => $user->id,
        'login_id' => $user->login_id,
    ]);
});

it('rejects /api/user without authentication', function () {
    $this->getJson('/api/user')->assertStatus(401);
});

it('logs out and revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $response->assertStatus(200);
    expect($user->tokens()->count())->toBe(0);
});
