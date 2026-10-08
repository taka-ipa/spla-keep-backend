<?php

namespace App\Http\Requests;

use App\Models\MatchRating;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 所有者チェックはコントローラ側で行う（モデルバインディング後でないとどのmatchか分からないため）
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
            'played_at' => ['sometimes', 'nullable', 'date'],
            'mode' => ['sometimes', 'required', 'string', 'max:255'],
            'rule' => ['sometimes', 'required', 'string', 'max:255'],
            'stage' => ['sometimes', 'required', 'string', 'max:255'],
            'weapon' => ['sometimes', 'required', 'string', 'max:255'],
            'is_win' => ['sometimes', 'nullable', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],

            // ratings自体を省略すれば評価は変更しない。送る場合は全件まるごと置き換える
            'ratings' => ['sometimes', 'array', 'min:1'],
            'ratings.*.task_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tasks', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'ratings.*.rating' => ['required', Rule::in(MatchRating::RATINGS)],
        ];
    }
}
