<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskProgress;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('user_id', auth()->id())
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '' || mb_strlen($title) > 255) {
            return response()->json([
                'message' => 'Title is required and must be at most 255 characters.',
            ], 422);
        }

        $priority = $data['priority'] ?? 'normal';
        if (!in_array($priority, ['low', 'normal', 'high'], true)) {
            return response()->json([
                'message' => 'Priority must be low, normal, or high.',
            ], 422);
        }

        $dueDate = $data['due_date'] ?? null;
        if ($dueDate !== null && $dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            return response()->json([
                'message' => 'due_date must use YYYY-MM-DD.',
            ], 422);
        }

        $progress = TaskProgress::normalize($data['progress'] ?? 0);

        $task = Task::create([
            'user_id'      => auth()->id(),
            'list_id'      => isset($data['list_id']) ? (int) $data['list_id'] : null,
            'title'        => $title,
            'description'  => trim((string) ($data['description'] ?? '')),
            'is_important' => !empty($data['is_important']),
            'priority'     => $priority,
            'progress'     => $progress,
            'due_date'     => $dueDate ?: null,
            'completed'    => TaskProgress::isComplete($progress),
        ]);

        Cache::forget('dashboard-user-' . auth()->id());

        return response()->json(['data' => $task], 201);
    }

    public function summary(Request $request)
    {
        $userId = auth()->id();
        $baseQuery = Task::where('user_id', $userId)->whereNull('deleted_at');

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('completed', true)->count();

        return response()->json(['data' => [
            'total'           => $total,
            'completed'       => $completed,
            'completion_rate' => $total === 0 ? 0 : round($completed * 100 / $total, 1),
        ]]);
    }

    public function adminSummary(Request $request)
    {
        return response()->json(['data' => [
            'users' => User::count(),
            'tasks' => Task::whereNull('deleted_at')->count(),
        ]]);
    }
}
