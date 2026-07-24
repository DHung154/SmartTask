<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login()
    {
        $this->requireGuest();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }
        $this->view('auth/login', ['title' => "\u{0110}\u{0103}ng nh\u{1EAD}p"]);
    }

    private function handleLogin()
    {
        $this->requireCsrf('/login');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        if ($email === '') $errors['email'] = "Vui l\u{00F2}ng nh\u{1EAD}p email.";
        if ($password === '') $errors['password'] = "Vui l\u{00F2}ng nh\u{1EAD}p m\u{1EAD}t kh\u{1EA9}u.";
        if ($errors) {
            $this->backWithErrors('/login', $errors);
            return;
        }

        $user = $this->userModel->verify($email, $password);
        if (!$user) {
            Session::setOld(['email' => $email]);
            Session::flash('error', "Email ho\u{1EB7}c m\u{1EAD}t kh\u{1EA9}u kh\u{00F4}ng \u{0111}\u{00FA}ng.");
            $this->redirect('/login');
            return;
        }

        Session::start();
        session_regenerate_id(true);
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_role', $user['role'] ?? 'user');
        Session::flash('success', "Ch\u{00E0}o m\u{1EEB}ng tr\u{1EDF} l\u{1EA1}i, " . $user['name'] . '!');
        $this->redirect('/');
    }

    public function register()
    {
        $this->requireGuest();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
            return;
        }
        $this->view('auth/register', ['title' => "\u{0110}\u{0103}ng k\u{00FD}"]);
    }

    private function handleRegister()
    {
        $this->requireCsrf('/register');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $errors = [];

        if ($name === '') $errors['name'] = "Vui l\u{00F2}ng nh\u{1EAD}p h\u{1ECD} t\u{00EA}n.";
        elseif (mb_strlen($name) > 100) $errors['name'] = "H\u{1ECD} t\u{00EA}n kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c v\u{01B0}\u{1EE3}t qu\u{00E1} 100 k\u{00FD} t\u{1EF1}.";
        if ($email === '') $errors['email'] = "Vui l\u{00F2}ng nh\u{1EAD}p email.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email kh\u{00F4}ng \u{0111}\u{00FA}ng \u{0111}\u{1ECB}nh d\u{1EA1}ng.";
        elseif (mb_strlen($email) > 255) $errors['email'] = "Email qu\u{00E1} d\u{00E0}i.";
        elseif ($this->userModel->emailExists($email)) $errors['email'] = "Email n\u{00E0}y \u{0111}\u{00E3} \u{0111}\u{01B0}\u{1EE3}c \u{0111}\u{0103}ng k\u{00FD}.";
        if ($password === '') $errors['password'] = "Vui l\u{00F2}ng nh\u{1EAD}p m\u{1EAD}t kh\u{1EA9}u.";
        elseif (strlen($password) < 6) $errors['password'] = "M\u{1EAD}t kh\u{1EA9}u ph\u{1EA3}i c\u{00F3} \u{00ED}t nh\u{1EA5}t 6 k\u{00FD} t\u{1EF1}.";
        if ($confirmPassword === '') $errors['confirm_password'] = "Vui l\u{00F2}ng nh\u{1EAD}p l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u.";
        elseif ($password !== $confirmPassword) $errors['confirm_password'] = "M\u{1EAD}t kh\u{1EA9}u nh\u{1EAD}p l\u{1EA1}i kh\u{00F4}ng kh\u{1EDB}p.";
        if ($errors) {
            $this->backWithErrors('/register', $errors);
            return;
        }

        if ($this->userModel->create(['name' => $name, 'email' => $email, 'password' => $password])) {
            Session::flash('success', "\u{0110}\u{0103}ng k\u{00FD} th\u{00E0}nh c\u{00F4}ng! M\u{1EDD}i b\u{1EA1}n \u{0111}\u{0103}ng nh\u{1EAD}p.");
            $this->redirect('/login');
            return;
        }
        $this->backWithErrors('/register', [], "\u{0110}\u{0103}ng k\u{00FD} th\u{1EA5}t b\u{1EA1}i. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.");
    }

    public function logout()
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
