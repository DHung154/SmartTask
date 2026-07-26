<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Logger;
use PDO;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Khởi tạo kết nối CSDL trực tiếp theo chuẩn PDO của PHP
     */
    private function getDbConnection(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_DATABASE'] ?? 'todo_schema';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Hàm hỗ trợ kiểm tra CSRF token đơn giản
     */
    private function checkCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = Session::get('csrf_token');
        if (empty($token) || $token !== $sessionToken) {
            if (method_exists($this, 'validateCsrf')) {
                $this->validateCsrf();
            }
        }
    }

    // Hiển thị danh sách các nhóm
    public function index(): void
    {
        $userId = Session::get('user_id');
        $db = $this->getDbConnection();

        $stmt = $db->prepare("
            SELECT t.*, tm.role as user_role, u.name as owner_name,
                   (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as total_members
            FROM teams t
            JOIN team_members tm ON t.id = tm.team_id
            JOIN users u ON t.owner_id = u.id
            WHERE tm.user_id = :user_id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        $teams = $stmt->fetchAll();

        $this->view('teams/index', [
            'teams' => $teams,
            'active_page' => 'teams'
        ]);
    }

    // Trang hiển thị giao diện form tạo nhóm mới (GET)
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $this->view('teams/create', [
            'active_page' => 'teams'
        ]);
    }

    // Xử lý lưu tạo nhóm mới (POST)
    public function store(): void
    {
        $this->checkCsrf();

        $userId = Session::get('user_id');
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Tên nhóm không được để trống.');
            $this->redirect('/teams/create');
            return;
        }

        $db = $this->getDbConnection();
        $db->beginTransaction();

        try {
            // 1. Thêm bản ghi vào bảng teams
            $stmt = $db->prepare("INSERT INTO teams (name, description, owner_id) VALUES (:name, :desc, :owner_id)");
            $stmt->execute([
                'name' => $name,
                'desc' => $description,
                'owner_id' => $userId
            ]);
            $teamId = $db->lastInsertId();

            // 2. Thêm người tạo làm 'owner' vào bảng team_members
            $stmtMember = $db->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (:team_id, :user_id, 'owner')");
            $stmtMember->execute([
                'team_id' => $teamId,
                'user_id' => $userId
            ]);

            $db->commit();
            Session::flash('success', 'Đã tạo nhóm thành công!');
            $this->redirect('/teams/detail?id=' . $teamId);
            return;
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error('team.create_failed', ['message' => $e->getMessage()]);
            Session::flash('error', 'Tạo nhóm thất bại!');
            $this->redirect('/teams');
            return;
        }
    }

    // Trang chi tiết nhóm
    public function detail(): void
    {
        $userId = Session::get('user_id');
        $teamId = (int)($_GET['id'] ?? 0);
        $db = $this->getDbConnection();

        $stmtRole = $db->prepare("SELECT role FROM team_members WHERE team_id = :team_id AND user_id = :user_id");
        $stmtRole->execute(['team_id' => $teamId, 'user_id' => $userId]);
        $myRole = $stmtRole->fetchColumn();

        if (!$myRole) {
            http_response_code(403);
            echo "403 Forbidden: Bạn không phải là thành viên của nhóm này.";
            return;
        }

        $stmt = $db->prepare("SELECT * FROM teams WHERE id = :id");
        $stmt->execute(['id' => $teamId]);
        $team = $stmt->fetch();

        $stmtMembers = $db->prepare("
            SELECT tm.*, u.name, u.email 
            FROM team_members tm
            JOIN users u ON tm.user_id = u.id
            WHERE tm.team_id = :team_id
            ORDER BY FIELD(tm.role, 'owner', 'admin', 'member')
        ");
        $stmtMembers->execute(['team_id' => $teamId]);
        $members = $stmtMembers->fetchAll();

        $stmtTasks = $db->prepare("
            SELECT t.*, u.name as author_name 
            FROM tasks t
            JOIN users u ON t.user_id = u.id
            WHERE t.team_id = :team_id AND t.deleted_at IS NULL
            ORDER BY t.created_at DESC
        ");
        $stmtTasks->execute(['team_id' => $teamId]);
        $tasks = $stmtTasks->fetchAll();

        $this->view('teams/detail', [
            'team' => $team,
            'myRole' => $myRole,
            'members' => $members,
            'tasks' => $tasks,
            'active_page' => 'teams'
        ]);
    }

    // Thêm thành viên
    public function addMember(): void
    {
        $this->checkCsrf();

        $userId = Session::get('user_id');
        $teamId = (int)($_POST['team_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'member';

        $db = $this->getDbConnection();

        $stmtCheck = $db->prepare("SELECT role FROM team_members WHERE team_id = :team_id AND user_id = :user_id");
        $stmtCheck->execute(['team_id' => $teamId, 'user_id' => $userId]);
        $myRole = $stmtCheck->fetchColumn();

        if (!in_array($myRole, ['owner', 'admin'])) {
            Session::flash('error', 'Bạn không có quyền thêm thành viên.');
            $this->redirect('/teams/detail?id=' . $teamId);
            return;
        }

        $stmtUser = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmtUser->execute(['email' => $email]);
        $targetUserId = $stmtUser->fetchColumn();

        if (!$targetUserId) {
            Session::flash('error', 'Không tìm thấy người dùng có email này.');
            $this->redirect('/teams/detail?id=' . $teamId);
            return;
        }

        $stmtExist = $db->prepare("SELECT id FROM team_members WHERE team_id = :team_id AND user_id = :user_id");
        $stmtExist->execute(['team_id' => $teamId, 'user_id' => $targetUserId]);
        if ($stmtExist->fetchColumn()) {
            Session::flash('error', 'Người dùng đã là thành viên của nhóm.');
            $this->redirect('/teams/detail?id=' . $teamId);
            return;
        }

        $stmtAdd = $db->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (:team_id, :user_id, :role)");
        $stmtAdd->execute(['team_id' => $teamId, 'user_id' => $targetUserId, 'role' => $role]);

        // Đẩy job gửi mail vào queue
        $payload = json_encode([
            'to_email' => $email,
            'subject' => 'Bạn vừa được thêm vào một nhóm mới!',
            'body' => "<h3>Thông Báo Nhóm</h3><p>Bạn đã được thêm vào nhóm làm việc trên SmartTask với vai trò <b>$role</b>.</p>"
        ]);
        $db->prepare("INSERT INTO job_queue (type, payload) VALUES ('send_team_task_email', :payload)")
           ->execute(['payload' => $payload]);

        Session::flash('success', 'Đã thêm thành viên mới!');
        $this->redirect('/teams/detail?id=' . $teamId);
    }

    // Xóa nhóm (chỉ dành cho Chủ nhóm/Owner)
    public function delete(): void
    {
        $this->checkCsrf();

        $userId = Session::get('user_id');
        $teamId = (int)($_POST['id'] ?? 0);

        if (!$userId || !$teamId) {
            Session::flash('error', 'Yêu cầu không hợp lệ.');
            $this->redirect('/teams');
            return;
        }

        $db = $this->getDbConnection();

        // Kiểm tra xem user có phải là owner của nhóm hay không
        $stmtCheck = $db->prepare("SELECT owner_id FROM teams WHERE id = :id");
        $stmtCheck->execute(['id' => $teamId]);
        $ownerId = $stmtCheck->fetchColumn();

        if ((int)$ownerId !== (int)$userId) {
            Session::flash('error', 'Bạn không có quyền xóa nhóm này.');
            $this->redirect('/teams');
            return;
        }

        $db->beginTransaction();

        try {
            // 1. Xóa các công việc thuộc nhóm này (nếu có)
            $db->prepare("DELETE FROM tasks WHERE team_id = :team_id")->execute(['team_id' => $teamId]);

            // 2. Xóa các thành viên trong nhóm
            $db->prepare("DELETE FROM team_members WHERE team_id = :team_id")->execute(['team_id' => $teamId]);

            // 3. Xóa nhóm
            $db->prepare("DELETE FROM teams WHERE id = :id")->execute(['id' => $teamId]);

            $db->commit();
            Session::flash('success', 'Đã xóa nhóm thành công!');
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error('team.delete_failed', ['message' => $e->getMessage()]);
            Session::flash('error', 'Lỗi khi xóa nhóm.');
        }

        $this->redirect('/teams');
    }
}