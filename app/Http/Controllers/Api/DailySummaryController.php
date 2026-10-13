<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\MatchRating;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailySummaryController extends Controller
{
    private const TIMEZONE = 'Asia/Tokyo';

    private const DAYS = 7;

    // GET /api/daily-summary
    public function index(Request $request)
    {
        $today = Carbon::today(self::TIMEZONE);
        $from = $today->copy()->subDays(self::DAYS - 1);

        $matches = $request->user()->gameMatches()
            ->with('ratings')
            ->whereBetween('created_at', [
                $from->copy()->startOfDay()->utc(),
                $today->copy()->endOfDay()->utc(),
            ])
            ->get();

        $matchesByDate = $matches->groupBy(
            fn (GameMatch $match) => $match->created_at->copy()->setTimezone(self::TIMEZONE)->toDateString()
        );

        $days = [];
        for ($i = 0; $i < self::DAYS; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $days[] = ['date' => $date] + $this->summarize($matchesByDate->get($date, collect()));
        }

        return response()->json([
            'range' => ['from' => $from->toDateString(), 'to' => $today->toDateString()],
            'totals' => $this->summarize($matches),
            'days' => $days,
        ]);
    }

    /**
     * @param  Collection<int, GameMatch>  $matches
     */
    private function summarize(Collection $matches): array
    {
        return $this->matchStats($matches) + ['ratings' => $this->ratingCounts($matches)];
    }

    /**
     * 評価記号ごとの件数。'-'（該当なし）は集計対象外。0件の記号も0で返す
     *
     * @param  Collection<int, GameMatch>  $matches
     * @return array<string, int>
     */
    private function ratingCounts(Collection $matches): array
    {
        $ratings = $matches->flatMap(fn (GameMatch $match) => $match->ratings);

        $counts = [];
        foreach (array_diff(MatchRating::RATINGS, ['-']) as $symbol) {
            $counts[$symbol] = $ratings->filter(fn (MatchRating $r) => $r->rating === $symbol)->count();
        }

        return $counts;
    }

    /**
     * @param  Collection<int, GameMatch>  $matches
     * @return array{matches: int, wins: int, losses: int, win_rate: float|null}
     */
    private function matchStats(Collection $matches): array
    {
        // is_winがnull（勝敗未入力）の試合は勝ちにも負けにも数えない。
        // Collection::where()は緩い比較でnull == falseになってしまうため、厳密比較でfilterする
        $wins = $matches->filter(fn (GameMatch $m) => $m->is_win === true)->count();
        $losses = $matches->filter(fn (GameMatch $m) => $m->is_win === false)->count();
        $decided = $wins + $losses;

        return [
            'matches' => $matches->count(),
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $decided > 0 ? round($wins / $decided * 100, 1) : null,
        ];
    }
}
