<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('sends a reset email whose link points to the frontend', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://app.example.test']);
    $user = User::factory()->create(['email' => 'sakebero@example.com']);

    $this->postJson('/api/forgot-password', ['email' => 'sakebero@example.com'])
        ->assertStatus(200);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return str_starts_with($url, 'https://app.example.test/reset-password?')
            && $query['email'] === 'sakebero@example.com'
            && $query['token'] !== '';
    });
});

it('returns the same response and sends nothing for an unregistered email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'sakebero@example.com']);

    $registered = $this->postJson('/api/forgot-password', ['email' => 'sakebero@example.com']);
    $unregistered = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

    $unregistered->assertStatus(200);
    expect($unregistered->json())->toBe($registered->json());
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    Notification::assertCount(1);
});

it('validates the email field', function () {
    $this->postJson('/api/forgot-password', [])->assertStatus(422)->assertJsonValidationErrors('email');
    $this->postJson('/api/forgot-password', ['email' => 'not-an-email'])
        ->assertStatus(422)->assertJsonValidationErrors('email');
});

it('is rate limited per IP', function () {
    Notification::fake();

    foreach (range(1, 6) as $i) {
        $this->postJson('/api/forgot-password', ['email' => "user{$i}@example.com"])->assertStatus(200);
    }

    $this->postJson('/api/forgot-password', ['email' => 'user7@example.com'])->assertStatus(429);
});

it('renders the reset email in Japanese', function () {
    app()->setLocale('ja');
    config(['app.name' => 'Spla Keep', 'app.frontend_url' => 'https://app.example.test']);
    $user = User::factory()->create(['name' => 'さけべろ', 'email' => 'sakebero@example.com']);

    $mail = (new ResetPassword('test-token'))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('【Spla Keep】パスワード再設定のご案内');
    expect($mail->greeting)->toBe('さけべろ さん');
    expect($mail->actionText)->toBe('パスワードを再設定する');
    expect($html)
        ->toContain('パスワード再設定のリクエストを受け付けました')
        ->toContain('このリンクの有効期限は60分です')
        ->toContain('ボタンをクリックできない場合は')
        ->toContain('https://app.example.test/reset-password?token=test-token')
        ->not->toContain('Regards')
        ->not->toContain('Hello');
});
