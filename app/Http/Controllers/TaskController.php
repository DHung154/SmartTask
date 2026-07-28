<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TodoList;
use App\Models\User;

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
            'assigned-to-me' => ['title' => 'Được giao cho tôi', 'empty' => 'Chưa ai giao việc gì cho bạn.'],
            'assigned-by-me' => ['title' => 'Tôi đã giao', 'empty' => 'Bạn chưa giao việc cho ai.'],
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
        $userId = auth()->id();

        $teams = Team::whereHas('members', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        return view('tasks.create', $this->baseData() + [
            'title'             => 'Thêm công việc',
            'preSelectedListId' => $request->query('list'),
            'active_filter'     => $request->query('list'),
            'teams'             => $teams,
            'members'           => $this->assignableMembers($userId),
            'teamMembers'       => $this->teamMemberMap($userId),
            'userTeamRoles'     => $this->userTeamRoles($userId),
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $data = $this->readTaskInput($request);

        if (!empty($data['team_id']) && !empty($data['assignee_id'])) {
            if (!$this->isTeamAdmin($data['team_id'], $userId)) {
                $data['assignee_id'] = null;
            }
        }

        $errors = $this->validateTask($data, $userId);

        $attachment = $this->handleAttachmentUpload($request, $userId);
        if (!empty($attachment['error'])) {
            $errors['attachment'] = $attachment['error'];
        }

        if ($errors) {
            return redirect('/tasks/create')->withErrors($errors)->withInput();
        }

        $data['user_id'] = $userId;

        $task = Task::create($data);

        if (!$task) {
            return redirect('/tasks/create')->withErrors([], 'Không lưu được công việc.')->withInput();
        }

        $this->storeAttachments($task, $attachment['files'] ?? [], $userId);

        ActivityLog::log($userId, 'create', 'task', $task->id, 'Thêm công việc: ' . $data['title']);
        $this->notifyAssignee($task, $userId);
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

        $task->load(['subtasks', 'comments.user:id,name', 'attachments', 'assignee:id,name']);

        return view('tasks.edit', $this->baseData() + [
            'title'        => 'Sửa công việc',
            'task'         => $task,
            'teams'        => $teams,
            'members'      => $this->assignableMembers($userId),
            'teamMembers'  => $this->teamMemberMap($userId),
            'userTeamRoles' => $this->userTeamRoles($userId),
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

        $effectiveTeamId = $data['team_id'] ?? $task->team_id;
        if (!$this->isTeamAdmin($effectiveTeamId, $userId)) {
            $data['assignee_id'] = $task->assignee_id;
        }

        $errors = $this->validateTask($data, $userId, $task->assignee_id);

        $existingCount = $task->attachments()->count();
        $attachment = $this->handleAttachmentUpload($request, $userId, $existingCount);
        if (!empty($attachment['error'])) {
            $errors['attachment'] = $attachment['error'];
        }

        if ($errors) {
            return redirect($backUrl)->withErrors($errors)->withInput();
        }

        if (optional($task->due_date)->toDateString() !== ($data['due_date'] ?? null)) {
            $data['reminder_sent_at'] = null;
            $data['reminder_queued_at'] = null;
        }

        // Có việc con thì tiến độ do việc con quyết định, bỏ qua input tay.
        if ($task->subtasks()->exists()) {
            unset($data['progress']);
        }

        $previousAssignee = $task->assignee_id;

        if ($task->update($data)) {
            $this->storeAttachments($task, $attachment['files'] ?? [], $userId);

            if ($task->subtasks()->exists()) {
                $task->refreshFromSubtasks();
            }

            ActivityLog::log($userId, 'update', 'task', $task->id, 'Cập nhật: ' . $data['title']);

            if ($task->assignee_id && $task->assignee_id !== $previousAssignee) {
                $this->notifyAssignee($task->fresh(), $userId);
            }

            Cache::forget('dashboard-user-' . $userId);
            session()->flash('success', 'Đã cập nhật công việc.');
            return redirect('/');
        }

        return redirect($backUrl)->withErrors([], 'Không cập nhật được công việc.')->withInput();
    }

    public function kanban()
    {
        $userId = auth()->id();
        $columns = array_fill_keys(Task::STATUSES, []);

        $tasks = Task::forUser($userId)
            ->with(['assignee:id,name', 'subtasks:id,task_id,completed'])
            ->get();

        foreach ($tasks as $task) {
            $columns[Task::normalizeStatus($task->status)][] = $task;
        }

        return view('tasks.kanban', $this->baseData() + [
            'title'        => 'Kanban',
            'active_page'  => 'kanban',
            'columns'      => $columns,
            'statusLabels' => Task::statusLabels(),
        ]);
    }

    /**
     * Đổi trạng thái task (kéo thả Kanban). Trả JSON cho request AJAX
     * để giao diện cập nhật tại chỗ, khỏi tải lại cả trang.
     */
    public function changeStatus(Request $request)
    {
        $userId = auth()->id();
        $taskId = (int) $request->input('id', 0);
        $status = Task::normalizeStatus($request->input('status'));

        $task = $this->findAccessibleTask($taskId, $userId);

        if (!$task) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Không tìm thấy công việc.'], 404);
            }
            session()->flash('error', 'Không cập nhật được trạng thái.');
            return redirect($this->backTarget($request, '/kanban'));
        }

        $task->applyStatus($status);
        ActivityLog::log($userId, 'status', 'task', $task->id, 'Đổi trạng thái: ' . $task->title);
        Cache::forget('dashboard-user-' . $userId);

        if ($request->expectsJson() || $request->ajax()) {
            $task->refresh();
            return response()->json(['data' => [
                'id'        => $task->id,
                'status'    => $task->status,
                'progress'  => (int) $task->progress,
                'completed' => (bool) $task->completed,
            ]]);
        }

        return redirect($this->backTarget($request, '/kanban'));
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
        } elseif ($task->subtasks()->exists()) {
            session()->flash('error', 'Công việc này có việc con, tiến độ tính tự động theo việc con.');
        } else {
            $task->update([
                'progress'  => $progress,
                'completed' => $progress >= 100,
                'status'    => $progress >= 100 ? 'done' : ($progress > 0 ? 'doing' : 'todo'),
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
                // Đồng bộ status/progress khi toggle: hoàn thành → done, bỏ → todo
                $wasCompleted = $task->completed;
                $task->applyStatus($wasCompleted ? 'todo' : 'done');

                // Hoàn thành một việc lặp lại thì sinh ngay lần kế tiếp.
                if (!$wasCompleted) {
                    $next = $task->fresh()->spawnNextOccurrence();
                    if ($next) {
                        session()->flash('success', 'Đã tạo lần kế tiếp, hạn ' . $next->due_date->format('d/m/Y') . '.');
                    }
                }
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
                    $this->purgeAttachmentFiles($task);
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
        $trashed = Task::onlyTrashed()->forUser($userId)->with('attachments')->get();
        $count = $trashed->count();

        foreach ($trashed as $task) {
            $this->purgeAttachmentFiles($task);
            $task->forceDelete();
        }

        if ($count) {
            Cache::forget('dashboard-user-' . $userId);
        }

        session()->flash('success', $count ? "Đã xóa {$count} công việc." : 'Thùng rác đang trống.');
        return redirect('/tasks?filter=trash');
    }

    /**
     * Xoá file vật lý của mọi đính kèm trước khi xoá task vĩnh viễn,
     * nếu không thư mục uploads sẽ đầy rác file mồ côi.
     */
    private function purgeAttachmentFiles(Task $task): void
    {
        foreach ($task->attachments()->get() as $attachment) {
            $file = $attachment->absolutePath();
            if ($attachment->path && is_file($file)) {
                @unlink($file);
            }
        }

        // File đính kèm kiểu cũ nằm trực tiếp trên bảng tasks.
        if ($task->attachment_path) {
            $legacy = public_path(ltrim($task->attachment_path, '/'));
            if (is_file($legacy)) {
                @unlink($legacy);
            }
        }
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

        $searchQuery = Task::forUser($userId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%');
            });

        $total = (clone $searchQuery)->count();
        $totalPages = max(1, (int) ceil($total / Task::PER_PAGE));
        $page = min($page, $totalPages);

        $tasks = $searchQuery->with(['assignee:id,name', 'subtasks:id,task_id,completed'])
            ->withCount('comments', 'attachments')
            ->applySort($sort)
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

        $progress = max(0, min(100, (int) $request->input('progress', 0)));
        $status = Task::normalizeStatus($request->input('status', 'todo'));

        if ($progress >= 100) {
            $status = 'done';
        } elseif ($status === 'done' && $progress < 100) {
            $progress = 100;
        }

        return [
            'title'        => trim($request->input('title', '')),
            'description'  => trim($request->input('description', '')),
            'list_id'      => $listId,
            'team_id'      => $teamId,
            'assignee_id'  => $request->filled('assignee_id') ? (int) $request->input('assignee_id') : null,
            'due_date'     => $request->filled('due_date') ? trim($request->input('due_date')) : null,
            'is_important' => $request->filled('is_important') ? 1 : 0,
            'priority'     => $request->input('priority', 'normal'),
            'progress'     => $progress,
            'status'       => $status,
            'completed'    => $status === 'done',
            'repeat'       => Task::normalizeRepeat($request->input('repeat', 'none')),
            'repeat_until' => $request->filled('repeat_until') ? trim($request->input('repeat_until')) : null,
        ];
    }

    private function validateTask(array $data, int $userId, ?int $existingAssigneeId = null): array
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

        // Chỉ giao việc được cho thành viên của đúng nhóm mà task thuộc về, và chỉ Admin/Owner nhóm mới được giao việc.
        if ($data['assignee_id'] !== null && $data['assignee_id'] !== $existingAssigneeId) {
            if (!$data['team_id']) {
                $errors['assignee_id'] = 'Chỉ giao được việc cho thành viên khi công việc thuộc một nhóm.';
            } elseif (!$this->canAssignTo($data['team_id'], $data['assignee_id'], $userId)) {
                $errors['assignee_id'] = 'Người được giao không thuộc nhóm này hoặc bạn không có quyền giao việc.';
            }
        }

        if ($data['repeat'] !== 'none' && $data['due_date'] === null) {
            $errors['repeat'] = 'Việc lặp lại phải có ngày hết hạn.';
        }

        if ($data['repeat_until'] !== null) {
            if (!$this->isValidDate($data['repeat_until'])) {
                $errors['repeat_until'] = 'Ngày kết thúc lặp không hợp lệ.';
            } elseif ($data['due_date'] !== null && $data['repeat_until'] < $data['due_date']) {
                $errors['repeat_until'] = 'Ngày kết thúc lặp phải sau ngày hết hạn.';
            }
        }

        return $errors;
    }

    /**
     * Báo cho người được giao việc: ghi nhật ký, dọn cache bảng điều khiển
     * của họ và gửi email. Email lỗi thì bỏ qua, không chặn luồng chính.
     */
    private function notifyAssignee(Task $task, int $actorId): void
    {
        if (!$task->assignee_id || (int) $task->assignee_id === $actorId) {
            return;
        }

        ActivityLog::log($task->assignee_id, 'assigned', 'task', $task->id, 'Được giao việc: ' . $task->title);
        Cache::forget('dashboard-user-' . $task->assignee_id);

        $assignee = User::find($task->assignee_id);
        if (!$assignee?->email) {
            return;
        }

        try {
            Mail::send('emails.task-assigned', [
                'task'     => $task,
                'assignee' => $assignee,
                'assigner' => User::find($actorId),
            ], function ($message) use ($assignee, $task) {
                $message->to($assignee->email)->subject('Bạn được giao việc: ' . $task->title);
            });
        } catch (\Throwable $e) {
            Log::warning('task.assign_email_failed', ['email' => $assignee->email, 'message' => $e->getMessage()]);
        }
    }

    private function isTeamAdmin($teamId, $userId): bool
    {
        if (!$teamId || !$userId) {
            return false;
        }

        $user = User::find($userId);
        if ($user && $user->role === 'admin') {
            return true;
        }

        return TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Người giao phải là Admin/Owner nhóm, và người nhận phải thuộc nhóm đó.
     */
    private function canAssignTo($teamId, $assigneeId, $userId): bool
    {
        if (!$this->isTeamAdmin($teamId, $userId)) {
            return false;
        }

        return TeamMember::where('team_id', $teamId)->where('user_id', $assigneeId)->exists();
    }

    private function userTeamRoles($userId): array
    {
        $user = User::find($userId);
        $isGlobalAdmin = ($user && $user->role === 'admin');

        $memberships = TeamMember::where('user_id', $userId)->get(['team_id', 'role']);
        $map = [];

        foreach ($memberships as $m) {
            $map[(int) $m->team_id] = $isGlobalAdmin ? 'admin' : $m->role;
        }

        return $map;
    }

    /** Thành viên của các nhóm mà user tham gia, để đổ vào ô chọn người nhận. */
    private function assignableMembers($userId)
    {
        return User::whereIn('id', function ($q) use ($userId) {
            $q->select('user_id')->from('team_members')
              ->whereIn('team_id', function ($sub) use ($userId) {
                  $sub->select('team_id')->from('team_members')->where('user_id', $userId);
              });
        })->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Map team_id => danh sách thành viên, để JS lọc ô "người nhận"
     * theo đúng nhóm đang chọn.
     */
    private function teamMemberMap($userId): array
    {
        $rows = TeamMember::whereIn('team_id', function ($q) use ($userId) {
                $q->select('team_id')->from('team_members')->where('user_id', $userId);
            })
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->get(['team_members.team_id', 'users.id', 'users.name']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->team_id][] = ['id' => (int) $row->id, 'name' => $row->name];
        }

        return $map;
    }

    /** Số file đính kèm tối đa cho một công việc. */
    const MAX_ATTACHMENTS = 5;

    /**
     * Nhận nhiều file từ input attachments[]. Trả về:
     *   ['files' => [ ['path','name','size'], ... ], 'error' => '...' ]
     * Chỉ ghi ra đĩa khi toàn bộ file đều hợp lệ, tránh để lại file mồ côi.
     */
    private function handleAttachmentUpload(Request $request, int $userId, int $existingCount = 0): array
    {
        $files = array_filter((array) $request->file('attachments', []));

        // Vẫn nhận input cũ tên 'attachment' cho tương thích ngược.
        if (!$files && $request->hasFile('attachment')) {
            $files = [$request->file('attachment')];
        }

        if (!$files) {
            return [];
        }

        if ($existingCount + count($files) > self::MAX_ATTACHMENTS) {
            return ['error' => 'Mỗi công việc tối đa ' . self::MAX_ATTACHMENTS . ' file đính kèm.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
        $prepared = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                return ['error' => 'Tải file lên không thành công.'];
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                return ['error' => 'Mỗi file đính kèm tối đa 5MB (' . $file->getClientOriginalName() . ').'];
            }

            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowed, true)) {
                return ['error' => 'Loại file không được hỗ trợ: .' . $ext];
            }

            $prepared[] = ['file' => $file, 'ext' => $ext];
        }

        $saved = [];
        foreach ($prepared as $item) {
            $filename = 'task_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $item['ext'];
            $size = $item['file']->getSize();
            $original = $item['file']->getClientOriginalName();

            $item['file']->move(public_path('uploads/tasks'), $filename);

            $saved[] = [
                'path' => '/uploads/tasks/' . $filename,
                'name' => mb_substr($original, 0, 255),
                'size' => (int) $size,
            ];
        }

        return ['files' => $saved];
    }

    /** Ghi các file vừa upload vào bảng task_attachments. */
    private function storeAttachments(Task $task, array $files, int $userId): void
    {
        foreach ($files as $file) {
            TaskAttachment::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'path'    => $file['path'],
                'name'    => $file['name'],
                'size'    => $file['size'],
            ]);
        }
    }

    /**
     * Gỡ một file đính kèm, xoá luôn file vật lý trên đĩa.
     */
    public function removeAttachment(Request $request)
    {
        $userId = auth()->id();
        $attachmentId = (int) $request->input('attachment_id', 0);

        $attachment = TaskAttachment::with('task')->find($attachmentId);
        $task = $attachment ? $this->findAccessibleTask($attachment->task_id, $userId) : null;

        if (!$attachment || !$task) {
            session()->flash('error', 'Không tìm thấy file đính kèm.');
            return redirect($this->backTarget($request));
        }

        $attachment->deleteWithFile();
        Cache::forget('dashboard-user-' . $userId);

        session()->flash('success', 'Đã gỡ file đính kèm.');
        return redirect('/tasks/edit?id=' . (int) $task->id);
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
