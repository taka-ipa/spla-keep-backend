<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $query = http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return rtrim(config('app.frontend_url'), '/').'/reset-password?'.$query;
        });
    }
}
