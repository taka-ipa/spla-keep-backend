<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

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
}
