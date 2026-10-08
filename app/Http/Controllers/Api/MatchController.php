<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMatchRequest;
use App\Models\GameMatch;
use App\Models\MatchRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    // GET /api/matches
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 5), 1), 100);

        $query = $request->user()->gameMatches()->orderByDesc('created_at');

        if ($request->filled('date')) {
            $day = \Carbon\Carbon::parse($request->string('date'), 'Asia/Tokyo');
            $query->whereBetween('created_at', [
                $day->copy()->startOfDay()->utc(),
                $day->copy()->endOfDay()->utc(),
            ]);
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage'));
        }

        if ($request->filled('weapon')) {
            $query->where('weapon', $request->string('weapon'));
        }

        return $query->paginate($perPage);
    }

    // GET /api/matches/{match}
    public function show(Request $request, GameMatch $match)
    {
        if ($match->user_id !== $request->user()->id) {
            abort(404);
        }

        $match->load('ratings.task');

        return response()->json([
            'match' => $match,
            'ratings' => $match->ratings->map(fn (MatchRating $rating) => [
                'task_id' => $rating->task_id,
                'title' => $rating->task->title,
                'rating' => $rating->rating,
            ]),
        ]);
    }

    // PATCH /api/matches/{match}
    public function update(UpdateMatchRequest $request, GameMatch $match)
    {
        if ($match->user_id !== $request->user()->id) {
            abort(404);
        }

        $validated = $request->validated();
        $ratings = $validated['ratings'] ?? null;
        unset($validated['ratings']);

        DB::transaction(function () use ($match, $validated, $ratings) {
            if ($validated !== []) {
                $match->update($validated);
            }

            // ratingsが送られてきた場合だけ、既存評価を全部消して新しいセットに丸ごと置き換える
            if ($ratings !== null) {
                $match->ratings()->delete();

                $match->ratings()->createMany(
                    collect($ratings)->map(fn (array $r) => [
                        'task_id' => $r['task_id'],
                        'rating' => $r['rating'],
                    ])->all()
                );
            }
        });

        return response()->json($match->fresh()->load('ratings.task'));
    }
}
