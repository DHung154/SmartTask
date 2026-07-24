<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TodoList;

class TaskController extends Controller
{
    private $taskModel;
    private $listModel;
    private $activityLog;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->listModel = new TodoList();
        $this->activityLog = new ActivityLog();
    }

    public static function filterMeta()
    {
        return [
            'inbox' => ['title' => "C\u{00F4}ng vi\u{1EC7}c", 'empty' => "Ch\u{01B0}a c\u{00F3} c\u{00F4}ng vi\u{1EC7}c n\u{00E0}o \u{1EDF} \u{0111}\u{00E2}y."],
            'my-day' => ['title' => "H\u{00F4}m nay", 'empty' => "H\u{00F4}m nay b\u{1EA1}n ch\u{01B0}a c\u{00F3} vi\u{1EC7}c n\u{00E0}o \u{0111}\u{1EBF}n h\u{1EA1}n."],
            'important' => ['title' => "Quan tr\u{1ECD}ng", 'empty' => "Ch\u{01B0}a c\u{00F3} vi\u{1EC7}c quan tr\u{1ECD}ng."],
            'planned' => ['title' => "C\u{00F3} h\u{1EA1}n ch\u{00F3}t", 'empty' => "Ch\u{01B0}a c\u{00F3} vi\u{1EC7}c n\u{00E0}o \u{0111}\u{1EB7}t h\u{1EA1}n ch\u{00F3}t."],
            'overdue' => ['title' => "Qu\u{00E1} h\u{1EA1}n", 'empty' => "B\u{1EA1}n kh\u{00F4}ng c\u{00F3} vi\u{1EC7}c n\u{00E0}o qu\u{00E1} h\u{1EA1}n."],
            'completed' => ['title' => "\u{0110}\u{00E3} ho\u{00E0}n th\u{00E0}nh", 'empty' => "Ch\u{01B0}a ho\u{00E0}n th\u{00E0}nh vi\u{1EC7}c n\u{00E0}o."],
            'incomplete' => ['title' => "Ch\u{01B0}a ho\u{00E0}n th\u{00E0}nh", 'empty' => "B\u{1EA1}n \u{0111}\u{00E3} xong h\u{1EBF}t m\u{1ECD}i vi\u{1EC7}c."],
            'all' => ['title' => "T\u{1EA5}t c\u{1EA3} c\u{00F4}ng vi\u{1EC7}c", 'empty' => "B\u{1EA1}n ch\u{01B0}a c\u{00F3} c\u{00F4}ng vi\u{1EC7}c n\u{00E0}o."],
            'trash' => ['title' => "Th\u{00F9}ng r\u{00E1}c", 'empty' => "Th\u{00F9}ng r\u{00E1}c tr\u{1ED1}ng."],
        ];
    }

    public function index()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $filter = $_GET['list'] ?? ($_GET['filter'] ?? 'inbox');
        $sort = Task::normalizeSort($_GET['sort'] ?? null);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $meta = self::filterMeta();
        $title = $meta['inbox']['title'];
        $emptyText = $meta['inbox']['empty'];
        $currentList = null;

        if (isset($meta[$filter])) {
            $title = $meta[$filter]['title'];
            $emptyText = $meta[$filter]['empty'];
        } elseif (is_numeric($filter)) {
            $currentList = $this->listModel->findById($filter, $userId);
            if (!$currentList) {
                Session::flash('error', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y danh s\u{00E1}ch.");
                $this->redirect('/');
                return;
            }
            $title = $currentList['name'];
            $emptyText = "Danh s\u{00E1}ch n\u{00E0}y ch\u{01B0}a c\u{00F3} c\u{00F4}ng vi\u{1EC7}c.";
        }

        $totalPages = $this->taskModel->totalPages($userId, $filter);
        $page = min($page, $totalPages);
        $this->view('tasks/index', $this->baseData($userId) + [
            'title' => $title,
            'emptyText' => $emptyText,
            'tasks' => $this->taskModel->getTasksByUserId($userId, $filter, $sort, $page),
            'active_filter' => $filter,
            'currentList' => $currentList,
            'sort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalTasks' => $this->taskModel->countByFilter($userId, $filter),
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveNewTask();
            return;
        }
        $userId = Session::get('user_id');
        $this->view('tasks/create', $this->baseData($userId) + [
            'title' => "Th\u{00EA}m c\u{00F4}ng vi\u{1EC7}c",
            'preSelectedListId' => $_GET['list'] ?? null,
            'active_filter' => $_GET['list'] ?? null,
        ]);
    }

    private function saveNewTask()
    {
        $this->requireCsrf('/tasks/create');
        $userId = Session::get('user_id');
        $data = $this->readTaskInput();
        $errors = $this->validateTask($data, $userId);
        $attachment = $this->handleAttachmentUpload($userId);
        if (!empty($attachment['error'])) $errors['attachment'] = $attachment['error'];
        if ($errors) {
            $this->backWithErrors('/tasks/create', $errors);
            return;
        }
        $data += ['user_id' => $userId, 'attachment_path' => $attachment['path'] ?? null, 'attachment_name' => $attachment['name'] ?? null];
        $taskId = $this->taskModel->create($data);
        if (!$taskId) {
            $this->backWithErrors('/tasks/create', [], "Kh\u{00F4}ng l\u{01B0}u \u{0111}\u{01B0}\u{1EE3}c c\u{00F4}ng vi\u{1EC7}c.");
            return;
        }
        $this->activityLog->log($userId, 'create', 'task', $taskId, "Th\u{00EA}m c\u{00F4}ng vi\u{1EC7}c: " . $data['title']);
        Cache::forgetDashboard((int) $userId);
        Session::flash('success', "\u{0110}\u{00E3} th\u{00EA}m c\u{00F4}ng vi\u{1EC7}c \"" . $data['title'] . '".');
        $this->redirect($data['list_id'] ? '/tasks?list=' . $data['list_id'] : '/');
    }

    public function quickAdd()
    {
        $this->requireAuth();
        $this->requirePost();
        $backTo = $this->backTarget();
        $this->requireCsrf($backTo);
        $userId = Session::get('user_id');
        $title = trim($_POST['title'] ?? '');
        if ($title === '' || mb_strlen($title) > 200) {
            Session::flash('error', $title === '' ? "H\u{00E3}y nh\u{1EAD}p t\u{00EA}n c\u{00F4}ng vi\u{1EC7}c." : "T\u{00EA}n c\u{00F4}ng vi\u{1EC7}c t\u{1ED1}i \u{0111}a 200 k\u{00FD} t\u{1EF1}.");
            $this->redirect($backTo);
            return;
        }
        $filter = $_POST['filter'] ?? '';
        $listId = is_numeric($filter) && $this->listModel->findById($filter, $userId) ? (int)$filter : null;
        $taskId = $this->taskModel->create([
            'user_id' => $userId, 'list_id' => $listId, 'title' => $title, 'description' => '',
            'due_date' => $filter === 'my-day' ? date('Y-m-d') : null,
            'is_important' => (int)($filter === 'important'), 'priority' => 'normal', 'progress' => 0,
        ]);
        if ($taskId) {
            $this->activityLog->log($userId, 'create', 'task', $taskId, "Th\u{00EA}m nhanh: " . $title);
            Cache::forgetDashboard((int) $userId);
        }
        Session::flash('success', "\u{0110}\u{00E3} th\u{00EA}m \"" . $title . '".');
        $this->redirect($backTo);
    }

    public function edit()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $taskId = $_GET['id'] ?? null;
        $task = $taskId ? $this->taskModel->findById($taskId, $userId) : false;
        if (!$task) {
            Session::flash('error', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y c\u{00F4}ng vi\u{1EC7}c.");
            $this->redirect('/');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveEditedTask($task);
            return;
        }
        $this->view('tasks/edit', $this->baseData($userId) + ['title' => "S\u{1EED}a c\u{00F4}ng vi\u{1EC7}c", 'task' => $task]);
    }

    private function saveEditedTask($task)
    {
        $userId = Session::get('user_id');
        $backUrl = '/tasks/edit?id=' . (int)$task['id'];
        $this->requireCsrf($backUrl);
        $data = $this->readTaskInput();
        $errors = $this->validateTask($data, $userId);
        $attachment = $this->handleAttachmentUpload($userId);
        if (!empty($attachment['error'])) $errors['attachment'] = $attachment['error'];
        if ($errors) {
            $this->backWithErrors($backUrl, $errors);
            return;
        }
        $data['attachment_path'] = !empty($_POST['remove_attachment']) ? null : ($task['attachment_path'] ?? null);
        $data['attachment_name'] = !empty($_POST['remove_attachment']) ? null : ($task['attachment_name'] ?? null);
        if (!empty($attachment['path'])) {
            $data['attachment_path'] = $attachment['path'];
            $data['attachment_name'] = $attachment['name'];
        }
        if ($this->taskModel->update($task['id'], $data, $userId)) {
            $this->activityLog->log($userId, 'update', 'task', $task['id'], "C\u{1EAD}p nh\u{1EAD}t: " . $data['title']);
            Cache::forgetDashboard((int) $userId);
            Session::flash('success', "\u{0110}\u{00E3} c\u{1EAD}p nh\u{1EAD}t c\u{00F4}ng vi\u{1EC7}c.");
            $this->redirect('/');
            return;
        }
        $this->backWithErrors($backUrl, [], "Kh\u{00F4}ng c\u{1EAD}p nh\u{1EAD}t \u{0111}\u{01B0}\u{1EE3}c c\u{00F4}ng vi\u{1EC7}c.");
    }

    public function kanban()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $columns = ['todo' => [], 'doing' => [], 'done' => []];
        foreach ($this->taskModel->getAllByUser($userId) as $task) {
            $progress = (int)($task['progress'] ?? ($task['completed'] ? 100 : 0));
            $columns[$progress >= 100 ? 'done' : ($progress > 0 ? 'doing' : 'todo')][] = $task;
        }
        $this->view('tasks/kanban', $this->baseData($userId) + ['title' => 'Kanban', 'active_page' => 'kanban', 'columns' => $columns]);
    }

    public function progress()
    {
        $this->requireAuth();
        $this->requirePost();
        $backTo = $this->backTarget('/kanban');
        $this->requireCsrf($backTo);
        $taskId = (int)($_POST['id'] ?? 0);
        $progress = max(0, min(100, (int)($_POST['progress'] ?? 0)));
        if (!$taskId || !$this->taskModel->updateProgress($taskId, Session::get('user_id'), $progress)) {
            Session::flash('error', "Kh\u{00F4}ng c\u{1EAD}p nh\u{1EAD}t \u{0111}\u{01B0}\u{1EE3}c ti\u{1EBF}n \u{0111}\u{1ED9}.");
        } else {
            Cache::forgetDashboard((int) Session::get('user_id'));
        }
        $this->redirect($backTo);
    }

    public function toggle() { $this->simpleTaskAction('toggleComplete', 'toggle'); }
    public function star() { $this->simpleTaskAction('toggleImportant', 'star'); }

    private function simpleTaskAction($method, $action)
    {
        $this->requireAuth();
        $this->requirePost();
        $backTo = $this->backTarget();
        $this->requireCsrf($backTo);
        $taskId = (int)($_POST['id'] ?? 0);
        if (!$taskId || !$this->taskModel->{$method}($taskId, Session::get('user_id'))) {
            Session::flash('error', "Kh\u{00F4}ng c\u{1EAD}p nh\u{1EAD}t \u{0111}\u{01B0}\u{1EE3}c c\u{00F4}ng vi\u{1EC7}c.");
        } else {
            $this->activityLog->log(Session::get('user_id'), $action, 'task', $taskId, "C\u{1EAD}p nh\u{1EAD}t c\u{00F4}ng vi\u{1EC7}c");
            Cache::forgetDashboard((int) Session::get('user_id'));
        }
        $this->redirect($backTo);
    }

    public function delete() { $this->trashAction('delete', '/', "chuy\u{1EC3}n v\u{00E0}o th\u{00F9}ng r\u{00E1}c"); }
    public function restore() { $this->trashAction('restore', '/tasks?filter=trash', "kh\u{00F4}i ph\u{1EE5}c"); }
    public function forceDelete() { $this->trashAction('forceDelete', '/tasks?filter=trash', "x\u{00F3}a v\u{0129}nh vi\u{1EC5}n"); }

    private function trashAction($method, $default, $label)
    {
        $this->requireAuth();
        $this->requirePost();
        $backTo = $this->backTarget($default);
        $this->requireCsrf($backTo);
        $taskId = (int)($_POST['id'] ?? 0);
        if ($taskId && $this->taskModel->{$method}($taskId, Session::get('user_id'))) {
            Cache::forgetDashboard((int) Session::get('user_id'));
            Session::flash('success', "\u{0110}\u{00E3} " . $label . " c\u{00F4}ng vi\u{1EC7}c.");
        } else {
            Session::flash('error', "Kh\u{00F4}ng th\u{1EC3} " . $label . " c\u{00F4}ng vi\u{1EC7}c.");
        }
        $this->redirect($backTo);
    }

    public function emptyTrash()
    {
        $this->requireAuth();
        $this->requirePost();
        $this->requireCsrf('/tasks?filter=trash');
        $count = $this->taskModel->emptyTrash(Session::get('user_id'));
        if ($count) Cache::forgetDashboard((int) Session::get('user_id'));
        Session::flash('success', $count ? "\u{0110}\u{00E3} x\u{00F3}a {$count} c\u{00F4}ng vi\u{1EC7}c." : "Th\u{00F9}ng r\u{00E1}c \u{0111}ang tr\u{1ED1}ng.");
        $this->redirect('/tasks?filter=trash');
    }

    public function search()
    {
        $this->requireAuth();
        $query = trim($_GET['q'] ?? '');
        if ($query === '') { $this->redirect('/'); return; }
        $userId = Session::get('user_id');
        $sort = Task::normalizeSort($_GET['sort'] ?? null);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = $this->taskModel->countSearchResults($userId, $query);
        $totalPages = max(1, (int)ceil($total / Task::PER_PAGE));
        $page = min($page, $totalPages);
        $this->view('tasks/index', $this->baseData($userId) + [
            'title' => "K\u{1EBF}t qu\u{1EA3} t\u{00EC}m \"" . $query . '"',
            'emptyText' => "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y c\u{00F4}ng vi\u{1EC7}c ph\u{00F9} h\u{1EE3}p.",
            'tasks' => $this->taskModel->searchTasks($userId, $query, $sort, $page),
            'active_filter' => 'search', 'currentList' => null, 'search_query' => $query,
            'sort' => $sort, 'page' => $page, 'totalPages' => $totalPages, 'totalTasks' => $total,
        ]);
    }

    private function readTaskInput()
    {
        return [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'list_id' => !empty($_POST['list_id']) ? (int)$_POST['list_id'] : null,
            'due_date' => !empty($_POST['due_date']) ? trim($_POST['due_date']) : null,
            'is_important' => !empty($_POST['is_important']) ? 1 : 0,
            'priority' => $_POST['priority'] ?? 'normal',
            'progress' => max(0, min(100, (int)($_POST['progress'] ?? 0))),
        ];
    }

    private function validateTask($data, $userId)
    {
        $errors = [];
        if ($data['title'] === '') $errors['title'] = "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00EA}n c\u{00F4}ng vi\u{1EC7}c.";
        elseif (mb_strlen($data['title']) > 200) $errors['title'] = "T\u{00EA}n c\u{00F4}ng vi\u{1EC7}c t\u{1ED1}i \u{0111}a 200 k\u{00FD} t\u{1EF1}.";
        if ($data['due_date'] !== null && !$this->isValidDate($data['due_date'])) $errors['due_date'] = "Ng\u{00E0}y h\u{1EBF}t h\u{1EA1}n kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.";
        if (!in_array($data['priority'], ['low', 'normal', 'high'], true)) $errors['priority'] = "M\u{1EE9}c \u{01B0}u ti\u{00EA}n kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.";
        if ($data['list_id'] !== null && !$this->listModel->findById($data['list_id'], $userId)) $errors['list_id'] = "Danh s\u{00E1}ch kh\u{00F4}ng t\u{1ED3}n t\u{1EA1}i.";
        return $errors;
    }

    private function handleAttachmentUpload($userId)
    {
        if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) return [];
        if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) return ['error' => "T\u{1EA3}i file l\u{00EA}n kh\u{00F4}ng th\u{00E0}nh c\u{00F4}ng."];
        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) return ['error' => "File \u{0111}\u{00ED}nh k\u{00E8}m t\u{1ED1}i \u{0111}a 5MB."];
        $original = basename($_FILES['attachment']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'], true)) return ['error' => "Lo\u{1EA1}i file kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c h\u{1ED7} tr\u{1EE3}."];
        $dir = dirname(__DIR__, 2) . '/public/uploads/tasks';
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) return ['error' => "Kh\u{00F4}ng t\u{1EA1}o \u{0111}\u{01B0}\u{1EE3}c th\u{01B0} m\u{1EE5}c upload."];
        $filename = 'task_' . (int)$userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $dir . '/' . $filename)) return ['error' => "Kh\u{00F4}ng l\u{01B0}u \u{0111}\u{01B0}\u{1EE3}c file."];
        return ['path' => '/uploads/tasks/' . $filename, 'name' => mb_substr($original, 0, 255)];
    }

    private function isValidDate($date)
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }

    private function backTarget($default = '/')
    {
        $target = $_POST['redirect'] ?? '';
        return is_string($target) && preg_match('#^/(?![\\\\/])#', $target) ? $target : $default;
    }

    private function baseData($userId)
    {
        return Cache::remember('dashboard-user-' . (int) $userId, 90, function () use ($userId) {
            $lists = $this->listModel->getListsByUserId($userId);
            return ['userLists' => $lists, 'taskCounts' => $this->taskModel->getTaskCounts($userId, $lists), 'stats' => $this->taskModel->getStatistics($userId)];
        });
    }
}
