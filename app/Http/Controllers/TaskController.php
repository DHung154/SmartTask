<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\Team;
use App\Models\ActivityLog;

class TaskController extends Controller
{
    public static function filterMeta(): array
    {
        return [
            'inbox'      => ['title' => 'Công việc', 'empty' => 'Chưa có công việc nào ở đây.'],
            'my-day'     => ['title' => 'Hôm nay', 'empty' => 'Hôm nay bạn chưa có việc nào đến hạn.'],
            'important'  => ['title' => 'Quan trọng', 'empty' => 'Chưa có việc quan trọng.'],
            'planned'    => ['title' => 'Có hạn chót', 'empty' => 'Chưa có việc nào đặt hạn chót.'],
            'overdue'    => ['title' => 'Quá hạn', 'empty' => 'Bạn không có việc nào quá hạn.'],
            'completed'  => ['title' => 'Đã hoàn thành', 'empty' => 'Chưa hoàn thành việc nào.'],
            'incomplete' => ['title' => 'Chưa hoàn thành', 'empty' => 'Bạn đã xong hết mọi việc.'],
            'all'        => ['title' => 'Tất cả công việc', 'empty' => 'Bạn chưa có công việc nào.'],
            'trash'      => ['title' => 'Thùng rác', 'empty' => 'Thùng rác trống.'],
        ];
    }

    public function index(Request $request)
    {
        $userId = auth()->id();
        $filter = $request->query('list', $request->query('filter', 'inbox'));
        $sort = Task::normalizeSort($request->query('sort'));
        $page = max(1, (int) $request->query('page', 1));

        $meta = self::filterMeta();
        $title = $meta['inbox']['title'];
        $emptyText = $meta['inbox']['empty'];
        $currentList = null;

        if (isset($meta[$filter])) {
            $title = $meta[$filter]['title'];
            $emptyText = $meta[$filter]['empty'];
        } elseif (is_numeric($filter)) {
            $currentList = TodoList::where('id', $filter)->where('user_id', $userId)->first();
            if (!$currentList) {
                session()->flash('error', 'Không tìm thấy danh sách.');
                return redirect('/');
            }
            $title = $currentList->name;
            $emptyText = 'Danh sách này chưa có công việc.';
        }

        $paginator = Task::getFilteredTasks($userId, $filter, $sort, $page);
        $totalPages = $paginator->lastPage();
        $page = min($page, max(1, $totalPages));

        return view('tasks.index', $this->baseData() + [
            'title'         => $title,
            'emptyText'     => $emptyText,
            'tasks'         => $paginator,
            'active_filter' => $filter,
            'currentList'   => $currentList,
            'sort'          => $sort,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalTasks'    => $paginator->total(),
        ]);
    }

    public function create(Request $request)
    {
        return view('tasks.create', $this->baseData() + [
            'title'             => 'Thêm công việc',
            'preSelectedListId' => $request->query('list'),
            'active_filter'     => $request->query('list'),
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $data = $this->readTaskInput($request);
        $errors = $this->validateTask($data, $userId);

        $attachment = $this->handleAttachmentUpload($request, $userId);
        if (!empty($attachment['error'])) {
            $errors['attachment'] = $attachment['error'];
        }

        if ($errors) {
            return redirect('/tasks/create')->withErrors($errors)->withInput();
        }

        $data['user_id'] = $userId;
        $data['attachment_path'] = $attachment['path'] ?? null;
        $data['attachment_name'] = $attachment['name'] ?? null;

        $task = Task::create($data);

        if (!$task) {
            return redirect('/tasks/create')->withErrors([], 'Không lưu được công việc.')->withInput();
        }

        ActivityLog::log($userId, 'create', 'task', $task->id, 'Thêm công việc: ' . $data['title']);
        Cache::forget('dashboard-user-' . $userId);

        session()->flash('success', 'Đã thêm công việc "' . $data['title'] . '".');
        return redirect($data['list_id'] ? '/tasks?list=' . $data['list_id'] : '/');
    }

    public function quickAdd(Request $request)
    {
        $backTo = $this->backTarget($request);
        $userId = auth()->id();
        $title = trim($request->input('title', ''));

        if ($title === '' || mb_strlen($title) > 200) {
            session()->flash('error', $title === '' ? 'Hãy nhập tên công việc.' : 'Tên công việc tối đa 200 ký tự.');
            return redirect($backTo);
        }

        $filter = $request->input('filter', '');
        $listId = null;
        if (is_numeric($filter) && TodoList::where('id', $filter)->where('user_id', $userId)->exists()) {
            $listId = (int) $filter;
        }

        $task = Task::create([
            'user_id'      => $userId,
            'list_id'      => $listId,
            'title'        => $title,
            'description'  => '',
            'due_date'     => $filter === 'my-day' ? now()->toDateString() : null,
            'is_important' => (int) ($filter === 'important'),
            'priority'     => 'normal',
            'progress'     => 0,
        ]);

        if ($task) {
            ActivityLog::log($userId, 'create', 'task', $task->id, 'Thêm nhanh: ' . $title);
            Cache::forget('dashboard-user-' . $userId);
        }

        session()->flash('success', 'Đã thêm "' . $title . '".');
        return redirect($backTo);
    }

    public function edit(Request $request)
    {
        $userId = auth()->id();
        $taskId = $request->query('id');
        $task = $this->findAccessibleTask($taskId, $userId);

        if (!$task) {
            session()->flash('error', 'Không tìm thấy công việc.');
            return redirect('/');
        }

        $teams = Team::whereHas('members', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        return view('tasks.edit', $this->baseData() + [
            'title' => 'Sửa công việc',
            'task'  => $task,
            'teams' => $teams,
        ]);
    }

    public function update(Request $request)
    {
        $userId = auth()->id();
        $taskId = $request->input('id');
        $task = $this->findAccessibleTask($taskId, $userId);

        if (!$task) {
            session()->flash('error', 'Không tìm thấy công việc.');
            return redirect('/');
        }

        $backUrl = '/tasks/edit?id=' . (int) $task->id;
        $data = $this->readTaskInput($request);
        $errors = $this->validateTask($data, $userId);

        $attachment = $this->handleAttachmentUpload($request, $userId);
        if (!empty($attachment['error'])) {
            $errors['attachment'] = $attachment['error'];
        }

        if ($errors) {
            return redirect($backUrl)->withErrors($errors)->withInput();
        }

        // Handle attachment: keep, replace, or remove
        $data['attachment_path'] = $request->input('remove_attachment')
            ? null
            : ($task->attachment_path ?? null);
        $data['attachment_name'] = $request->input('remove_attachment')
            ? null
            : ($task->attachment_name ?? null);

        if (!empty($attachment['path'])) {
            $data['attachment_path'] = $attachment['path'];
            $data['attachment_name'] = $attachment['name'];
        }

        if (optional($task->due_date)->toDateString() !== ($data['due_date'] ?? null)) {
            $data['reminder_sent_at'] = null;
            $data['reminder_queued_at'] = null;
        }

        if ($task->update($data)) {
            ActivityLog::log($userId, 'update', 'task', $task->id, 'Cập nhật: ' . $data['title']);
            Cache::forget('dashboard-user-' . $userId);
            session()->flash('success', 'Đã cập nhật công việc.');
            return redirect('/');
        }

        return redirect($backUrl)->withErrors([], 'Không cập nhật được công việc.')->withInput();
    }

    public function kanban()
    {
        $userId = auth()->id();
        $columns = ['todo' => [], 'doing' => [], 'done' => []];

        $tasks = Task::where('user_id', $userId)->get();
        foreach ($tasks as $task) {
            $progress = (int) ($task->progress ?? ($task->completed ? 100 : 0));
            $key = $progress >= 100 ? 'done' : ($progress > 0 ? 'doing' : 'todo');
            $columns[$key][] = $task;
        }

        return view('tasks.kanban', $this->baseData() + [
            'title'       => 'Kanban',
            'active_page' => 'kanban',
            'columns'     => $columns,
        ]);
    }

    public function progress(Request $request)
    {
        $backTo = $this->backTarget($request, '/kanban');
        $userId = auth()->id();
        $taskId = (int) $request->input('id', 0);
        $progress = max(0, min(100, (int) $request->input('progress', 0)));

        $task = $this->findAccessibleTask($taskId, $userId);

        if (!$task) {
            session()->flash('error', 'Không cập nhật được tiến độ.');
        } else {
            $task->update([
                'progress'  => $progress,
                'completed' => $progress >= 100,
            ]);
            Cache::forget('dashboard-user-' . $userId);
        }

        return redirect($backTo);
    }

    public function toggle(Request $request)
    {
        return $this->simpleTaskAction($request, 'toggle');
    }

    public function star(Request $request)
    {
        return $this->simpleTaskAction($request, 'star');
    }

    private function simpleTaskAction(Request $request, string $action)
    {
        $backTo = $this->backTarget($request);
        $userId = auth()->id();
        $taskId = (int) $request->input('id', 0);

        $task = $this->findAccessibleTask($taskId, $userId);

        if (!$task) {
            session()->flash('error', 'Không cập nhật được công việc.');
        } else {
            if ($action === 'toggle') {
                // Đồng bộ progress khi toggle: hoàn thành → 100%, bỏ → 0%
                $newCompleted = !$task->completed;
                $task->update([
                    'completed' => $newCompleted,
                    'progress'  => $newCompleted ? 100 : 0,
                ]);
            } else {
                $task->update(['is_important' => !$task->is_important]);
            }
            ActivityLog::log($userId, $action, 'task', $taskId, 'Cập nhật công việc');
            Cache::forget('dashboard-user-' . $userId);
        }

        return redirect($backTo);
    }

    public function delete(Request $request)
    {
        return $this->trashAction($request, 'delete', '/', 'chuyển vào thùng rác');
    }

    public function restore(Request $request)
    {
        return $this->trashAction($request, 'restore', '/tasks?filter=trash', 'khôi phục');
    }

    public function forceDelete(Request $request)
    {
        return $this->trashAction($request, 'forceDelete', '/tasks?filter=trash', 'xóa vĩnh viễn');
    }

    private function trashAction(Request $request, string $method, string $default, string $label)
    {
        $backTo = $this->backTarget($request, $default);
        $userId = auth()->id();
        $taskId = (int) $request->input('id', 0);

        $success = false;
        if ($taskId) {
            if ($method === 'restore') {
                $task = $this->findAccessibleTask($taskId, $userId, true);
                if ($task && $task->trashed()) {
                    $task->restore();
                    $success = true;
                }
            } elseif ($method === 'forceDelete') {
                $task = $this->findAccessibleTask($taskId, $userId, true);
                if ($task && $task->trashed()) {
                    $task->forceDelete();
                    $success = true;
                }
            } else {
                // soft delete
                $task = $this->findAccessibleTask($taskId, $userId);
                if ($task) {
                    $task->delete();
                    $success = true;
                }
            }
        }

        if ($success) {
            Cache::forget('dashboard-user-' . $userId);
            session()->flash('success', 'Đã ' . $label . ' công việc.');
        } else {
            session()->flash('error', 'Không thể ' . $label . ' công việc.');
        }

        return redirect($backTo);
    }

    public function emptyTrash(Request $request)
    {
        $userId = auth()->id();
        $count = Task::onlyTrashed()->where('user_id', $userId)->count();

        Task::onlyTrashed()->where('user_id', $userId)->forceDelete();

        if ($count) {
            Cache::forget('dashboard-user-' . $userId);
        }

        session()->flash('success', $count ? "Đã xóa {$count} công việc." : 'Thùng rác đang trống.');
        return redirect('/tasks?filter=trash');
    }

    public function search(Request $request)
    {
        $query = trim($request->query('q', ''));
        if ($query === '') {
            return redirect('/');
        }

        $userId = auth()->id();
        $sort = Task::normalizeSort($request->query('sort'));
        $page = max(1, (int) $request->query('page', 1));

        $searchQuery = Task::where('user_id', $userId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%');
            });

        $total = (clone $searchQuery)->count();
        $totalPages = max(1, (int) ceil($total / Task::PER_PAGE));
        $page = min($page, $totalPages);

        $tasks = $searchQuery->applySort($sort)
            ->paginate(Task::PER_PAGE, ['*'], 'page', $page);

        return view('tasks.index', $this->baseData() + [
            'title'         => 'Kết quả tìm "' . $query . '"',
            'emptyText'     => 'Không tìm thấy công việc phù hợp.',
            'tasks'         => $tasks,
            'active_filter' => 'search',
            'currentList'   => null,
            'search_query'  => $query,
            'sort'          => $sort,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalTasks'    => $total,
        ]);
    }

    private function readTaskInput(Request $request): array
    {
        $rawTeamInput = $request->input('team_id', '');
        $teamId = null;
        $listId = null;

        if (!empty($rawTeamInput)) {
            if (strpos($rawTeamInput, 'list_') === 0) {
                $listId = (int) str_replace('list_', '', $rawTeamInput);
            } else {
                $teamId = (int) $rawTeamInput;
            }
        }

        return [
            'title'        => trim($request->input('title', '')),
            'description'  => trim($request->input('description', '')),
            'list_id'      => $listId,
            'team_id'      => $teamId,
            'due_date'     => $request->filled('due_date') ? trim($request->input('due_date')) : null,
            'is_important' => $request->filled('is_important') ? 1 : 0,
            'priority'     => $request->input('priority', 'normal'),
            'progress'     => max(0, min(100, (int) $request->input('progress', 0))),
        ];
    }

    private function validateTask(array $data, int $userId): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'Vui lòng nhập tên công việc.';
        } elseif (mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Tên công việc tối đa 200 ký tự.';
        }

        if ($data['due_date'] !== null && !$this->isValidDate($data['due_date'])) {
            $errors['due_date'] = 'Ngày hết hạn không hợp lệ.';
        }

        if (!in_array($data['priority'], ['low', 'normal', 'high'], true)) {
            $errors['priority'] = 'Mức ưu tiên không hợp lệ.';
        }

        if ($data['list_id'] !== null && !TodoList::where('id', $data['list_id'])->where('user_id', $userId)->exists()) {
            $errors['list_id'] = 'Danh sách không tồn tại.';
        }

        return $errors;
    }

    private function handleAttachmentUpload(Request $request, int $userId): array
    {
        if (!$request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        if (!$file->isValid()) {
            return ['error' => 'Tải file lên không thành công.'];
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['error' => 'File đính kèm tối đa 5MB.'];
        }

        $original = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'], true)) {
            return ['error' => 'Loại file không được hỗ trợ.'];
        }

        $filename = 'task_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $file->move(public_path('uploads/tasks'), $filename);

        return [
            'path' => '/uploads/tasks/' . $filename,
            'name' => mb_substr($original, 0, 255),
        ];
    }

    private function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private function backTarget(Request $request, string $default = '/'): string
    {
        $target = $request->input('redirect', '');
        return is_string($target) && preg_match('#^/(?![\\\\\/])#', $target) ? $target : $default;
    }

    /**
     * Tìm task mà user có quyền truy cập:
     * - Task do chính user tạo (user_id)
     * - HOẶC task thuộc team mà user là thành viên
     */
    private function findAccessibleTask($taskId, $userId, $includeDeleted = false)
    {
        if (!$taskId) return null;

        $query = $includeDeleted ? Task::withTrashed() : Task::query();

        return $query->where('id', $taskId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereIn('team_id', function ($sub) use ($userId) {
                      $sub->select('team_id')
                          ->from('team_members')
                          ->where('user_id', $userId);
                  });
            })
            ->first();
    }

    private function baseData(): array
    {
        $userId = auth()->id();
        return Cache::remember('dashboard-user-' . $userId, 90, function () use ($userId) {
            $userLists = TodoList::where('user_id', $userId)->orderBy('created_at')->get();
            return [
                'userLists'  => $userLists,
                'taskCounts' => Task::getTaskCounts($userId, $userLists),
                'stats'      => Task::getStatistics($userId),
            ];
        });
    }
}
