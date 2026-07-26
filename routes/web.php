<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;

// Health check
Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json(['status' => 'ok', 'database' => 'connected']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'database' => 'unavailable'], 503);
    }
});

Route::post('/locale', function (Request $request) {
    $locale = in_array($request->input('locale'), ['vi', 'en'], true)
        ? $request->input('locale')
        : 'vi';

    session(['locale' => $locale]);
    return back();
})->name('locale.set');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Tasks
    Route::get('/', [TaskController::class, 'index'])->name('home');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks/create', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/quick-add', [TaskController::class, 'quickAdd'])->name('tasks.quickAdd');
    Route::get('/tasks/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::post('/tasks/edit', [TaskController::class, 'update'])->name('tasks.update');
    Route::get('/tasks/search', [TaskController::class, 'search'])->name('tasks.search');
    Route::post('/tasks/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::post('/tasks/star', [TaskController::class, 'star'])->name('tasks.star');
    Route::post('/tasks/delete', [TaskController::class, 'delete'])->name('tasks.delete');
    Route::post('/tasks/restore', [TaskController::class, 'restore'])->name('tasks.restore');
    Route::post('/tasks/force-delete', [TaskController::class, 'forceDelete'])->name('tasks.forceDelete');
    Route::post('/tasks/empty-trash', [TaskController::class, 'emptyTrash'])->name('tasks.emptyTrash');
    Route::post('/tasks/progress', [TaskController::class, 'progress'])->name('tasks.progress');
    Route::get('/kanban', [TaskController::class, 'kanban'])->name('tasks.kanban');

    // Lists
    Route::get('/lists/create', [ListController::class, 'create'])->name('lists.create');
    Route::post('/lists/create', [ListController::class, 'store'])->name('lists.store');
    Route::get('/lists/edit', [ListController::class, 'edit'])->name('lists.edit');
    Route::post('/lists/edit', [ListController::class, 'update'])->name('lists.update');
    Route::post('/lists/delete', [ListController::class, 'destroy'])->name('lists.destroy');

    // Teams
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams/create', [TeamController::class, 'store'])->name('teams.store');
    Route::post('/teams/store', [TeamController::class, 'store']);
    Route::get('/teams/detail', [TeamController::class, 'detail'])->name('teams.detail');
    Route::post('/teams/add-member', [TeamController::class, 'addMember'])->name('teams.addMember');
    Route::post('/teams/remove-member', [TeamController::class, 'removeMember'])->name('teams.removeMember');
    Route::post('/teams/change-role', [TeamController::class, 'changeRole'])->name('teams.changeRole');
    Route::post('/teams/transfer', [TeamController::class, 'transferOwnership'])->name('teams.transfer');
    Route::post('/teams/leave', [TeamController::class, 'leave'])->name('teams.leave');
    Route::get('/teams/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::post('/teams/edit', [TeamController::class, 'update'])->name('teams.update');
    Route::post('/teams/delete', [TeamController::class, 'destroy'])->name('teams.destroy');

    // Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [UserController::class, 'changePassword'])->name('profile.password');

    // Reports
    Route::get('/calendar', [ReportController::class, 'calendar'])->name('reports.calendar');
    Route::get('/report', [ReportController::class, 'report'])->name('reports.report');
    Route::get('/activity', [ReportController::class, 'activity'])->name('reports.activity');
    Route::get('/report/export.csv', [ReportController::class, 'exportCsv'])->name('reports.exportCsv');
});
