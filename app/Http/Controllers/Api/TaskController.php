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
        $query = Task::forUser(auth()->id())
            ->with(['assignee:id,name'])
            ->withCount('subtasks', 'comments', 'attachments');

        if ($request->filled('status')) {
            $query->where('status', Task::normalizeStatus($request->query('status')));
        }

        if ($request->filled('completed')) {
            $query->where('completed', filter_var($request->query('completed'), FILTER_VALIDATE_BOOLEAN));
        }

        $tasks = $query->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'per_page'     => $tasks->perPage(),
                'total'        => $tasks->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $task = $this->findOwn($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        return response()->json([
            'data' => $task->load(['assignee:id,name', 'subtasks', 'comments.user:id,name', 'attachments']),
        ]);
    }

    public function store(Request $request)
    {
        $errors = $this->validatePayload($request->all(), true);

        if ($errors) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $data = $request->all();
        $progress = TaskProgress::normalize($data['progress'] ?? 0);
        $status = isset($data['status'])
            ? Task::normalizeStatus($data['status'])
            : ($progress >= 100 ? 'done' : ($progress > 0 ? 'doing' : 'todo'));

        $task = Task::create([
            'user_id'      => auth()->id(),
            'assignee_id'  => $data['assignee_id'] ?? null,
            'list_id'      => isset($data['list_id']) ? (int) $data['list_id'] : null,
            'title'        => trim((string) $data['title']),
            'description'  => trim((string) ($data['description'] ?? '')),
            'is_important' => !empty($data['is_important']),
            'priority'     => $data['priority'] ?? 'normal',
            'progress'     => $progress,
            'status'       => $status,
            'due_date'     => ($data['due_date'] ?? null) ?: null,
            'completed'    => $status === 'done',
        ]);

        Cache::forget('dashboard-user-' . auth()->id());

        return response()->json(['data' => $task], 201);
    }

    public function update(Request $request, int $id)
    {
        $task = $this->findOwn($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $errors = $this->validatePayload($request->all(), false);

        if ($errors) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $data = $request->all();
        $updates = [];

        foreach (['title', 'description'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = trim((string) $data[$field]);
            }
        }

        if (array_key_exists('priority', $data))     $updates['priority'] = $data['priority'];
        if (array_key_exists('is_important', $data)) $updates['is_important'] = (bool) $data['is_important'];
        if (array_key_exists('due_date', $data))     $updates['due_date'] = $data['due_date'] ?: null;
        if (array_key_exists('assignee_id', $data))  $updates['assignee_id'] = $data['assignee_id'] ?: null;

        // Task có việc con thì tiến độ do việc con quyết định, không nhận từ API.
        if (array_key_exists('progress', $data) && !$task->subtasks()->exists()) {
            $progress = TaskProgress::normalize($data['progress']);
            $updates['progress'] = $progress;
            $updates['status'] = $progress >= 100 ? 'done' : ($progress > 0 ? 'doing' : 'todo');
            $updates['completed'] = $progress >= 100;
        }

        if ($updates) {
            $task->update($updates);
        }

        // status truyền vào được ưu tiên và sẽ đồng bộ lại completed/progress.
        if (array_key_exists('status', $data)) {
            $task->applyStatus(Task::normalizeStatus($data['status']));
        }

        Cache::forget('dashboard-user-' . auth()->id());

        return response()->json(['data' => $task->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $task = $this->findOwn($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $task->delete();
        Cache::forget('dashboard-user-' . auth()->id());

        return response()->json(['message' => 'Task moved to trash.']);
    }

    public function summary(Request $request)
    {
        $userId = auth()->id();
        $baseQuery = Task::forUser($userId);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('completed', true)->count();

        $byStatus = [];
        foreach (Task::STATUSES as $status) {
            $byStatus[$status] = (clone $baseQuery)->where('status', $status)->count();
        }

        return response()->json(['data' => [
            'total'           => $total,
            'completed'       => $completed,
            'completion_rate' => $total === 0 ? 0 : round($completed * 100 / $total, 1),
            'by_status'       => $byStatus,
            'overdue'         => (clone $baseQuery)->where('completed', false)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())->count(),
        ]]);
    }

    public function adminSummary(Request $request)
    {
        return response()->json(['data' => [
            'users' => User::count(),
            'tasks' => Task::count(),
        ]]);
    }

    private function findOwn(int $id): ?Task
    {
        return Task::forUser(auth()->id())->where('id', $id)->first();
    }

    /**
     * @param bool $requireTitle true khi tạo mới, false khi cập nhật một phần.
     */
    private function validatePayload(array $data, bool $requireTitle): array
    {
        $errors = [];

        if ($requireTitle || array_key_exists('title', $data)) {
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $errors['title'] = 'Title is required.';
            } elseif (mb_strlen($title) > 200) {
                $errors['title'] = 'Title must be at most 200 characters.';
            }
        }

        if (array_key_exists('priority', $data) && !in_array($data['priority'], ['low', 'normal', 'high'], true)) {
            $errors['priority'] = 'Priority must be low, normal, or high.';
        }

        if (array_key_exists('status', $data) && !in_array($data['status'], Task::STATUSES, true)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', Task::STATUSES) . '.';
        }

        if (!empty($data['due_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
            $errors['due_date'] = 'due_date must use YYYY-MM-DD.';
        }

        return $errors;
    }
}
