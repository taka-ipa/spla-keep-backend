<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // GET /api/tasks
    public function index(Request $request)
    {
        return $request->user()->tasks()->orderBy('sort_order')->get();
    }

    // POST /api/tasks
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return response()->json($task, 201);
    }

    // PATCH /api/tasks/{task}
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    // DELETE /api/tasks/{task}
    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            abort(404);
        }

        $task->delete();

        return response()->json(null, 204);
    }
}
