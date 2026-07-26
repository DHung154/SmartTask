<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/tasks', [TaskController::class, 'index']);
    Route::post('/v1/tasks', [TaskController::class, 'store']);
    Route::get('/v1/summary', [TaskController::class, 'summary']);
    Route::get('/v1/admin/summary', [TaskController::class, 'adminSummary'])->middleware('admin');
});
