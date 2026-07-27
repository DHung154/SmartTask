<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;

// Lấy token: POST /api/v1/login với email + password.
Route::post('/v1/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/me', [AuthController::class, 'me']);
    Route::post('/v1/logout', [AuthController::class, 'logout']);

    Route::get('/v1/tasks', [TaskController::class, 'index']);
    Route::post('/v1/tasks', [TaskController::class, 'store']);
    Route::get('/v1/tasks/{id}', [TaskController::class, 'show'])->whereNumber('id');
    Route::put('/v1/tasks/{id}', [TaskController::class, 'update'])->whereNumber('id');
    Route::patch('/v1/tasks/{id}', [TaskController::class, 'update'])->whereNumber('id');
    Route::delete('/v1/tasks/{id}', [TaskController::class, 'destroy'])->whereNumber('id');

    Route::get('/v1/summary', [TaskController::class, 'summary']);
    Route::get('/v1/admin/summary', [TaskController::class, 'adminSummary'])->middleware('admin');
});
