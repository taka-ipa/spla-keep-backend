<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['played_at', 'mode', 'rule', 'stage', 'weapon', 'is_win', 'note'])]
class GameMatch extends Model
{
    /** @use HasFactory<\Database\Factories\GameMatchFactory> */
    use HasFactory;

    // クラス名はGameMatchだが、テーブル名はmatch予約語を避けつつ実体に合わせてmatchesにする
    protected $table = 'matches';

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'is_win' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(MatchRating::class, 'match_id');
    }
}
