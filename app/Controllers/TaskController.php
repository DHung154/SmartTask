<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\Team; // <-- Bổ sung Model Team

class TaskController extends Controller
{
    private $taskModel;
    private $listModel;
    private $activityLog;
    private $teamModel; // <-- Khai báo biến teamModel

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->listModel = new TodoList();
        $this->activityLog = new ActivityLog();
        $this->teamModel = new Team(); // <-- Khởi tạo Team model
    }

    public static function filterMeta()
    {
        return [
            'inbox' => ['title' => "Công việc", 'empty' => "Chưa có công việc nào ở đây."],
            'my-day' => ['title' => "Hôm nay", 'empty' => "Hôm nay bạn chưa có việc nào đến hạn."],
            'important' => ['title' => "Quan trọng", 'empty' => "Chưa có việc quan trọng."],
            'planned' => ['title' => "Có hạn chót", 'empty' => "Chưa có việc nào đặt hạn chót."],
            'overdue' => ['title' => "Quá hạn", 'empty' => "Bạn không có việc nào quá hạn."],
            'completed' => ['title' => "Đã hoàn thành", 'empty' => "Chưa hoàn thành việc nào."],
            'incomplete' => ['title' => "Chưa hoàn thành", 'empty' => "Bạn đã xong hết mọi việc."],
            'all' => ['title' => "Tất cả công việc", 'empty' => "Bạn chưa có công việc nào."],
            'trash' => ['title' => "Thùng rác", 'empty' => "Thùng rác trống."],
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
                Session::flash('error', "Không tìm thấy danh sách.");
                $this->redirect('/');
                return;
            }
            $title = $currentList['name'];
            $emptyText = "Danh sách này chưa có công việc.";
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
            'title' => "Thêm công việc",
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
            $this->backWithErrors('/tasks/create', [], "Không lưu được công việc.");
            return;
        }
        $this->activityLog->log($userId, 'create', 'task', $taskId, "Thêm công việc: " . $data['title']);
        Cache::forgetDashboard((int) $userId);
        Session::flash('success', "Đã thêm công việc \"" . $data['title'] . '".');
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
            Session::flash('error', $title === '' ? "Hãy nhập tên công việc." : "Tên công việc tối đa 200 ký tự.");
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
            $this->activityLog->log($userId, 'create', 'task', $taskId, "Thêm nhanh: " . $title);
            Cache::forgetDashboard((int) $userId);
        }
        Session::flash('success', "Đã thêm \"" . $title . '".');
        $this->redirect($backTo);
    }

    public function edit()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $taskId = $_GET['id'] ?? null;
        $task = $taskId ? $this->taskModel->findById($taskId, $userId) : false;
        if (!$task) {
            Session::flash('error', "Không tìm thấy công việc.");
            $this->redirect('/');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveEditedTask($task);
            return;
        }

        // BỔ SUNG: Lấy danh sách nhóm của user để truyền sang view edit.php
        $teams = method_exists($this->teamModel, 'getTeamsByUserId') 
            ? $this->teamModel->getTeamsByUserId($userId) 
            : [];

        $this->view('tasks/edit', $this->baseData($userId) + [
            'title' => "Sửa công việc", 
            'task' => $task,
            'teams' => $teams // <-- Truyền mảng teams ra view
        ]);
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
            $this->activityLog->log($userId, 'update', 'task', $task['id'], "Cập nhật: " . $data['title']);
            Cache::forgetDashboard((int) $userId);
            Session::flash('success', "Đã cập nhật công việc.");
            $this->redirect('/');
            return;
        }
        $this->backWithErrors($backUrl, [], "Không cập nhật được công việc.");
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
            Session::flash('error', "Không cập nhật được tiến độ.");
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
            Session::flash('error', "Không cập nhật được công việc.");
        } else {
            $this->activityLog->log(Session::get('user_id'), $action, 'task', $taskId, "Cập nhật công việc");
            Cache::forgetDashboard((int) Session::get('user_id'));
        }
        $this->redirect($backTo);
    }

    public function delete() { $this->trashAction('delete', '/', "chuyển vào thùng rác"); }
    public function restore() { $this->trashAction('restore', '/tasks?filter=trash', "khôi phục"); }
    public function forceDelete() { $this->trashAction('forceDelete', '/tasks?filter=trash', "xóa vĩnh viễn"); }

    private function trashAction($method, $default, $label)
    {
        $this->requireAuth();
        $this->requirePost();
        $backTo = $this->backTarget($default);
        $this->requireCsrf($backTo);
        $taskId = (int)($_POST['id'] ?? 0);
        if ($taskId && $this->taskModel->{$method}($taskId, Session::get('user_id'))) {
            Cache::forgetDashboard((int) Session::get('user_id'));
            Session::flash('success', "Đã " . $label . " công việc.");
        } else {
            Session::flash('error', "Không thể " . $label . " công việc.");
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
        Session::flash('success', $count ? "Đã xóa {$count} công việc." : "Thùng rác đang trống.");
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
            'title' => "Kết quả tìm \"" . $query . '"',
            'emptyText' => "Không tìm thấy công việc phù hợp.",
            'tasks' => $this->taskModel->searchTasks($userId, $query, $sort, $page),
            'active_filter' => 'search', 'currentList' => null, 'search_query' => $query,
            'sort' => $sort, 'page' => $page, 'totalPages' => $totalPages, 'totalTasks' => $total,
        ]);
    }

    private function readTaskInput()
    {
        $rawTeamInput = $_POST['team_id'] ?? '';
        $teamId = null;
        $listId = null;

        // Xử lý phân tách nếu giá trị gửi lên dạng nhóm hay dạng list_id
        if (!empty($rawTeamInput)) {
            if (strpos($rawTeamInput, 'list_') === 0) {
                $listId = (int) str_replace('list_', '', $rawTeamInput);
            } else {
                $teamId = (int) $rawTeamInput;
            }
        }

        return [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'list_id' => $listId,
            'team_id' => $teamId, // Thêm trường team_id vào dữ liệu lưu trữ
            'due_date' => !empty($_POST['due_date']) ? trim($_POST['due_date']) : null,
            'is_important' => !empty($_POST['is_important']) ? 1 : 0,
            'priority' => $_POST['priority'] ?? 'normal',
            'progress' => max(0, min(100, (int)($_POST['progress'] ?? 0))),
        ];
    }

    private function validateTask($data, $userId)
    {
        $errors = [];
        if ($data['title'] === '') $errors['title'] = "Vui lòng nhập tên công việc.";
        elseif (mb_strlen($data['title']) > 200) $errors['title'] = "Tên công việc tối đa 200 ký tự.";
        if ($data['due_date'] !== null && !$this->isValidDate($data['due_date'])) $errors['due_date'] = "Ngày hết hạn không hợp lệ.";
        if (!in_array($data['priority'], ['low', 'normal', 'high'], true)) $errors['priority'] = "Mức ưu tiên không hợp lệ.";
        if ($data['list_id'] !== null && !$this->listModel->findById($data['list_id'], $userId)) $errors['list_id'] = "Danh sách không tồn tại.";
        return $errors;
    }

    private function handleAttachmentUpload($userId)
    {
        if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) return [];
        if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) return ['error' => "Tải file lên không thành công."];
        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) return ['error' => "File đính kèm tối đa 5MB."];
        $original = basename($_FILES['attachment']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'], true)) return ['error' => "Loại file không được hỗ trợ."];
        $dir = dirname(__DIR__, 2) . '/public/uploads/tasks';
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) return ['error' => "Không tạo được thư mục upload."];
        $filename = 'task_' . (int)$userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $dir . '/' . $filename)) return ['error' => "Không lưu được file."];
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