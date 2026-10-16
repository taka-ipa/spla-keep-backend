<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
