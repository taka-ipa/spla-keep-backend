<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// user_idはuser()リレーション経由での作成時に自動でセットするため、あえてFillableに含めない
#[Fillable(['date', 'slot', 'task_id', 'memo'])]
class DailyTaskAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\DailyTaskAssignmentFactory> */
    use HasFactory;

    // 課題A / 課題B
    public const SLOTS = [1, 2];

    protected function casts(): array
    {
        return [
            // JSONにしたとき "2026-10-16" の形で出力する（時刻やタイムゾーンを付けない）
            'date' => 'date:Y-m-d',
            'slot' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
