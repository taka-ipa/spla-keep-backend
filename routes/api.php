<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DailySummaryController;
use App\Http\Controllers\Api\DailyTaskAssignmentController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MatchesWithRatingsController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\TaskController;
use App\Models\DailyTaskAssignment;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// メールの連打による迷惑送信を防ぐため、IPごとに1分間に6回まで
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::post('/matches-with-ratings', [MatchesWithRatingsController::class, 'store']);

    Route::get('/matches', [MatchController::class, 'index']);
    Route::get('/matches/{match}', [MatchController::class, 'show']);
    Route::patch('/matches/{match}', [MatchController::class, 'update']);

    Route::get('/daily-summary', [DailySummaryController::class, 'index']);

    Route::get('/daily-assignments', [DailyTaskAssignmentController::class, 'index']);
    Route::put('/daily-assignments/{slot}', [DailyTaskAssignmentController::class, 'upsert'])
        ->whereIn('slot', DailyTaskAssignment::SLOTS);
    Route::delete('/daily-assignments/{slot}', [DailyTaskAssignmentController::class, 'destroy'])
        ->whereIn('slot', DailyTaskAssignment::SLOTS);
});
