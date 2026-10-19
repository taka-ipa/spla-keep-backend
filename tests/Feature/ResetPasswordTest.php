<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

function resetPayload(User $user, string $token, array $overrides = []): array
{
    return array_merge([
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ], $overrides);
}

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, $token))->assertStatus(200);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeFalse();
});

it('lets the user log in with the new password', function () {
    $user = User::factory()->create(['login_id' => 'sakebero', 'password' => 'old-password-123']);
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, $token))->assertStatus(200);

    $this->postJson('/api/login', ['login_id' => 'sakebero', 'password' => 'new-password-123'])
        ->assertStatus(200);
    $this->postJson('/api/login', ['login_id' => 'sakebero', 'password' => 'old-password-123'])
        ->assertStatus(422);
});

it('revokes all existing API tokens after a reset', function () {
    $user = User::factory()->create();
    $user->createToken('web');
    $user->createToken('web');
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, $token))->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});

it('does not allow reusing a token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, $token))->assertStatus(200);
    $this->postJson('/api/reset-password', resetPayload($user, $token, [
        'password' => 'another-password-1',
        'password_confirmation' => 'another-password-1',
    ]))->assertStatus(422)->assertJsonValidationErrors('token');
});

it('rejects an invalid token without changing the password', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);
    Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, 'wrong-token'))
        ->assertStatus(422)->assertJsonValidationErrors('token');

    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeTrue();
});

it('rejects an expired token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    $this->postJson('/api/reset-password', resetPayload($user, $token))
        ->assertStatus(422)->assertJsonValidationErrors('token');
});

it('returns the same error for an unknown email as for an invalid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $wrongToken = $this->postJson('/api/reset-password', resetPayload($user, 'wrong-token'));
    $unknownEmail = $this->postJson('/api/reset-password', resetPayload($user, $token, ['email' => 'nobody@example.com']));

    $unknownEmail->assertStatus(422);
    expect($unknownEmail->json())->toBe($wrongToken->json());
});

it('requires a confirmed password of at least 8 characters', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', resetPayload($user, $token, ['password_confirmation' => 'mismatch-123']))
        ->assertStatus(422)->assertJsonValidationErrors('password');
    $this->postJson('/api/reset-password', resetPayload($user, $token, [
        'password' => 'short', 'password_confirmation' => 'short',
    ]))->assertStatus(422)->assertJsonValidationErrors('password');
});
