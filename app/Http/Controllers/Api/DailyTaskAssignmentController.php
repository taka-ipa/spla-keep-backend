<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertDailyTaskAssignmentRequest;
use App\Models\DailyTaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyTaskAssignmentController extends Controller
{
    private const TIMEZONE = 'Asia/Tokyo';

    // GET /api/daily-assignments?date=YYYY-MM-DD （dateを省略すると今日(JST)）
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? Carbon::today(self::TIMEZONE)->toDateString();

        $assignments = $request->user()->dailyTaskAssignments()
            ->with('task')
            ->whereDate('date', $date)
            ->get()
            ->keyBy('slot');

        // 課題A/Bの2枠を常に返す。未設定の枠はtask=nullにする
        $slots = collect(DailyTaskAssignment::SLOTS)->map(function (int $slot) use ($assignments) {
            $assignment = $assignments->get($slot);

            return [
                'slot' => $slot,
                'task' => $assignment ? [
                    'id' => $assignment->task->id,
                    'title' => $assignment->task->title,
                ] : null,
                'memo' => $assignment?->memo,
            ];
        });

        return response()->json([
            'date' => $date,
            'assignments' => $slots,
        ]);
    }

    // PUT /api/daily-assignments/{slot}  （その日のそのスロットに課題を設定。設定済みなら差し替え）
    public function upsert(UpsertDailyTaskAssignmentRequest $request, int $slot)
    {
        $validated = $request->validated();
        $user = $request->user();

        // dateカラムはSQLiteだと時刻付きで保存されるため、whereDateで日付だけを比較して探す
        $assignment = $user->dailyTaskAssignments()
            ->whereDate('date', $validated['date'])
            ->where('slot', $slot)
            ->first()
            ?? $user->dailyTaskAssignments()->make(['date' => $validated['date'], 'slot' => $slot]);

        $assignment->fill([
            'task_id' => $validated['task_id'],
            'memo' => $validated['memo'] ?? null,
        ])->save();

        $assignment->load('task');

        return response()->json([
            'date' => $assignment->date->toDateString(),
            'slot' => $assignment->slot,
            'task' => [
                'id' => $assignment->task->id,
                'title' => $assignment->task->title,
            ],
            'memo' => $assignment->memo,
        ], $assignment->wasRecentlyCreated ? 201 : 200);
    }

    // DELETE /api/daily-assignments/{slot}?date=YYYY-MM-DD  （そのスロットの課題を解除。すでに空でも204）
    public function destroy(Request $request, int $slot)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? Carbon::today(self::TIMEZONE)->toDateString();

        $request->user()->dailyTaskAssignments()
            ->whereDate('date', $date)
            ->where('slot', $slot)
            ->delete();

        return response()->json(null, 204);
    }
}
