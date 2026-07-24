<?php

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Cache;
use App\Entities\TaskRecord;
use App\Entities\UserRecord;
use App\Support\TaskProgress;

final class ApiTaskController
{
    public function index(): never
    {
        $tasks = TaskRecord::query()->where('user_id', (int) $_SERVER['APP_API_USER_ID'])
            ->whereNull('deleted_at')->orderByDesc('created_at')->get();
        JsonResponse::send(['data' => $tasks]);
    }

    public function summary(): never
    {
        $query = TaskRecord::query()->where('user_id', (int) $_SERVER['APP_API_USER_ID'])->whereNull('deleted_at');
        $total = (clone $query)->count();
        $completed = (clone $query)->where('completed', true)->count();
        JsonResponse::send(['data' => [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total === 0 ? 0 : round($completed * 100 / $total, 1),
        ]]);
    }

    public function create(): never
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = is_array($data) ? $data : $_POST;
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 255) {
            JsonResponse::send(['message' => 'Title is required and must be at most 255 characters.'], 422);
        }
        $priority = $data['priority'] ?? 'normal';
        if (!in_array($priority, ['low', 'normal', 'high'], true)) {
            JsonResponse::send(['message' => 'Priority must be low, normal, or high.'], 422);
        }
        $dueDate = $data['due_date'] ?? null;
        if ($dueDate !== null && $dueDate !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dueDate)) {
            JsonResponse::send(['message' => 'due_date must use YYYY-MM-DD.'], 422);
        }

        $progress = TaskProgress::normalize($data['progress'] ?? 0);
        $task = TaskRecord::query()->create([
            'user_id' => (int) $_SERVER['APP_API_USER_ID'],
            'list_id' => isset($data['list_id']) ? (int) $data['list_id'] : null,
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')),
            'is_important' => !empty($data['is_important']),
            'priority' => $priority,
            'progress' => $progress,
            'due_date' => $dueDate ?: null,
            'completed' => TaskProgress::isComplete($progress),
        ]);
        Cache::forgetDashboard((int) $_SERVER['APP_API_USER_ID']);
        JsonResponse::send(['data' => $task], 201);
    }

    public function adminSummary(): never
    {
        JsonResponse::send(['data' => [
            'users' => UserRecord::query()->count(),
            'tasks' => TaskRecord::query()->whereNull('deleted_at')->count(),
        ]]);
    }
}
