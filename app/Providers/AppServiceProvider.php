<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 再設定メールのリンクは、このAPIではなくフロントエンドの再設定画面に向ける
        $resetUrl = function (object $notifiable, string $token): string {
            $query = http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return rtrim(config('app.frontend_url'), '/').'/reset-password?'.$query;
        };

        ResetPassword::createUrlUsing($resetUrl);

        // 再設定メールの本文（日本語）。toMailUsingのコールバックはURLを渡してくれないので、上のクロージャで作る
        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($resetUrl): MailMessage {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('【'.config('app.name').'】パスワード再設定のご案内')
                ->greeting($notifiable->name.' さん')
                ->line('パスワード再設定のリクエストを受け付けました。下のボタンから新しいパスワードを設定してください。')
                ->action('パスワードを再設定する', $resetUrl($notifiable, $token))
                ->line("このリンクの有効期限は{$minutes}分です。")
                ->line('心当たりがない場合は、このメールを破棄してください。パスワードは変更されません。')
                ->salutation(config('app.name'));
        });
    }
}
