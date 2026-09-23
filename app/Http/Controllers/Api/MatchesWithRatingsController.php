<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatchWithRatingsRequest;
use Illuminate\Support\Facades\DB;

class MatchesWithRatingsController extends Controller
{
    // POST /api/matches-with-ratings
    public function store(StoreMatchWithRatingsRequest $request)
    {
        $validated = $request->validated();

        $match = DB::transaction(function () use ($request, $validated) {
            $match = $request->user()->gameMatches()->create([
                'played_at' => $validated['played_at'] ?? null,
                'mode' => $validated['mode'],
                'rule' => $validated['rule'],
                'stage' => $validated['stage'],
                'weapon' => $validated['weapon'],
                'is_win' => $validated['is_win'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            $match->ratings()->createMany(
                collect($validated['ratings'])->map(fn (array $r) => [
                    'task_id' => $r['task_id'],
                    'rating' => $r['rating'],
                ])->all()
            );

            return $match;
        });

        return response()->json(
            $match->load('ratings.task'),
            201
        );
    }
}
