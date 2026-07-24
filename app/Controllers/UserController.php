<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Task;
use App\Models\TodoList;

class UserController extends Controller
{
    private $userModel;
    private $taskModel;
    private $listModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->taskModel = new Task();
        $this->listModel = new TodoList();
    }

    public function profile()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Session::destroy();
            $this->redirect('/login');
            return;
        }
        $userLists = $this->listModel->getListsByUserId($userId);
        $this->view('user/profile', [
            'title' => "T\u{00E0}i kho\u{1EA3}n c\u{1EE7}a t\u{00F4}i",
            'user' => $user,
            'userLists' => $userLists,
            'taskCounts' => $this->taskModel->getTaskCounts($userId, $userLists),
            'stats' => $this->taskModel->getStatistics($userId),
        ]);
    }

    public function updateProfile()
    {
        $this->requireAuth();
        $this->requirePost();
        $this->requireCsrf('/profile');
        $userId = Session::get('user_id');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $errors = [];
        if ($name === '') $errors['name'] = "Vui l\u{00F2}ng nh\u{1EAD}p h\u{1ECD} t\u{00EA}n.";
        elseif (mb_strlen($name) > 100) $errors['name'] = "H\u{1ECD} t\u{00EA}n kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c v\u{01B0}\u{1EE3}t qu\u{00E1} 100 k\u{00FD} t\u{1EF1}.";
        if ($email === '') $errors['email'] = "Vui l\u{00F2}ng nh\u{1EAD}p email.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email kh\u{00F4}ng \u{0111}\u{00FA}ng \u{0111}\u{1ECB}nh d\u{1EA1}ng.";
        elseif (mb_strlen($email) > 255) $errors['email'] = "Email qu\u{00E1} d\u{00E0}i.";
        elseif ($this->userModel->emailExists($email, $userId)) $errors['email'] = "Email n\u{00E0}y \u{0111}\u{00E3} \u{0111}\u{01B0}\u{1EE3}c ng\u{01B0}\u{1EDD}i kh\u{00E1}c s\u{1EED} d\u{1EE5}ng.";
        if ($errors) {
            $this->backWithErrors('/profile', $errors);
            return;
        }
        if ($this->userModel->updateProfile($userId, ['name' => $name, 'email' => $email])) {
            Session::set('user_name', $name);
            Session::flash('success', "\u{0110}\u{00E3} c\u{1EAD}p nh\u{1EAD}t th\u{00F4}ng tin t\u{00E0}i kho\u{1EA3}n.");
        } else {
            Session::flash('error', "Kh\u{00F4}ng c\u{1EAD}p nh\u{1EAD}t \u{0111}\u{01B0}\u{1EE3}c th\u{00F4}ng tin. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.");
        }
        $this->redirect('/profile');
    }

    public function changePassword()
    {
        $this->requireAuth();
        $this->requirePost();
        $this->requireCsrf('/profile');
        $userId = Session::get('user_id');
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $errors = [];
        if ($current === '') $errors['current_password'] = "Vui l\u{00F2}ng nh\u{1EAD}p m\u{1EAD}t kh\u{1EA9}u hi\u{1EC7}n t\u{1EA1}i.";
        elseif (!$this->userModel->verifyPassword($userId, $current)) $errors['current_password'] = "M\u{1EAD}t kh\u{1EA9}u hi\u{1EC7}n t\u{1EA1}i kh\u{00F4}ng \u{0111}\u{00FA}ng.";
        if ($new === '') $errors['new_password'] = "Vui l\u{00F2}ng nh\u{1EAD}p m\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i.";
        elseif (strlen($new) < 6) $errors['new_password'] = "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i ph\u{1EA3}i c\u{00F3} \u{00ED}t nh\u{1EA5}t 6 k\u{00FD} t\u{1EF1}.";
        elseif ($new === $current) $errors['new_password'] = "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i ph\u{1EA3}i kh\u{00E1}c m\u{1EAD}t kh\u{1EA9}u c\u{0169}.";
        if ($confirm === '') $errors['confirm_password'] = "Vui l\u{00F2}ng nh\u{1EAD}p l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i.";
        elseif ($new !== $confirm) $errors['confirm_password'] = "M\u{1EAD}t kh\u{1EA9}u nh\u{1EAD}p l\u{1EA1}i kh\u{00F4}ng kh\u{1EDB}p.";
        if ($errors) {
            Session::flash('open_tab', 'security');
            $this->backWithErrors('/profile', $errors);
            return;
        }
        if ($this->userModel->updatePassword($userId, $new)) {
            Session::start();
            session_regenerate_id(true);
            Session::flash('success', "\u{0110}\u{00E3} \u{0111}\u{1ED5}i m\u{1EAD}t kh\u{1EA9}u th\u{00E0}nh c\u{00F4}ng.");
        } else {
            Session::flash('error', "Kh\u{00F4}ng \u{0111}\u{1ED5}i \u{0111}\u{01B0}\u{1EE3}c m\u{1EAD}t kh\u{1EA9}u. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.");
        }
        $this->redirect('/profile');
    }
}
