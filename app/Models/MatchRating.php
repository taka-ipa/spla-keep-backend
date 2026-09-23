<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// match_idはmatch()リレーション経由での作成時に自動でセットするため、あえてFillableに含めない
#[Fillable(['task_id', 'rating'])]
class MatchRating extends Model
{
    /** @use HasFactory<\Database\Factories\MatchRatingFactory> */
    use HasFactory;

    // 許可する評価記号。バリデーションやテストデータ生成で使い回す唯一の定義元
    public const RATINGS = ['◎', '○', '△', '×', '-'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
