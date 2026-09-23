<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchWithRatingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // auth:sanctumミドルウェアで認証済みかは既にチェック済み。
        // ここでは「自分のリソースを作るだけ」なので追加の権限チェックは不要
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', 'max:255'],
            'rule' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', 'max:255'],
            'weapon' => ['required', 'string', 'max:255'],
            'played_at' => ['nullable', 'date'],
            'is_win' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],

            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*.task_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tasks', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'ratings.*.rating' => ['required', Rule::in(['○', '△', '×', '-'])],
        ];
    }
}
