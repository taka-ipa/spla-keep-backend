<?php

namespace App\Http\Requests;

use App\Models\DailyTaskAssignment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpsertDailyTaskAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // dateを省略した場合は今日(JST)として扱う
        if (! $this->filled('date')) {
            $this->merge(['date' => Carbon::today('Asia/Tokyo')->toDateString()]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'task_id' => [
                'required',
                'integer',
                // 自分の「有効な」課題だけ選べる
                Rule::exists('tasks', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)->where('is_active', true)
                ),
                $this->differentFromOtherSlot(),
            ],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * 同じ日の課題Aと課題Bに、同じ課題を入れさせない
     */
    private function differentFromOtherSlot(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $usedInOtherSlot = DailyTaskAssignment::query()
                ->where('user_id', $this->user()->id)
                ->whereDate('date', $this->input('date'))
                ->where('slot', '!=', $this->route('slot'))
                ->where('task_id', $value)
                ->exists();

            if ($usedInOtherSlot) {
                $fail('課題Aと課題Bには別々の課題を選んでください。');
            }
        };
    }
}
