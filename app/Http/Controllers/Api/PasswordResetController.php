<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    // POST /api/forgot-password
    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // 登録済みかどうかに関わらず同じレスポンスを返す。
        // 結果を出し分けると、他人のメールアドレスが登録済みか外部から調べられてしまうため
        Password::sendResetLink($validated);

        return response()->json([
            'message' => '登録されているメールアドレスであれば、パスワード再設定用のメールを送信しました。',
        ]);
    }

    // POST /api/reset-password
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // passwordカラムはhashed castなので、平文を代入すれば自動でハッシュ化される
                $user->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));
                $user->save();

                // 再設定後は、すべての端末のログイン(APIトークン)を失効させる
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // トークン不正・期限切れ・メールアドレス不一致のどれでも同じエラーにして、原因を外部に漏らさない
            throw ValidationException::withMessages([
                'token' => ['パスワード再設定のリンクが無効か、期限切れです。もう一度やり直してください。'],
            ]);
        }

        return response()->json([
            'message' => 'パスワードを再設定しました。新しいパスワードでログインしてください。',
        ]);
    }
}
